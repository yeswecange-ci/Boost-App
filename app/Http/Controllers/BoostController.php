<?php

namespace App\Http\Controllers;

use App\Models\BoostCampaign;
use App\Models\BoostRequest;
use App\Models\FacebookPage;
use App\Models\FacebookPost;
use Illuminate\Http\Request;
use App\Notifications\BoostSubmittedNotification;
use App\Models\User;

class BoostController extends Controller
{
    public function __construct() {}

    /**
     * Formulaire de création
     */
    public function create(Request $request)
    {
        $pageId  = $request->get('page_id');
        $postId  = $request->get('post_id');
        $page    = FacebookPage::where('page_id', $pageId)->firstOrFail();

        $postMaster = FacebookPost::where('post_id', $postId)
            ->where('facebook_page_id', $page->id)
            ->first();

        if (!$postMaster) {
            return redirect()->route('posts.index')->with('error', 'Post introuvable.');
        }

        $post = [
            'id'            => $postMaster->post_id,
            'message'       => $postMaster->message,
            'created_time'  => $postMaster->posted_at?->toIso8601String(),
            'thumbnail'     => $postMaster->thumbnail_url,
            'permalink_url' => $postMaster->permalink_url,
            'type'          => $postMaster->type,
        ];

        $currencies = ['XOF', 'EUR', 'USD'];
        $countries  = $this->getAfricanCountries();
        $interests  = $this->getInterestsList();

        return view('boost.create', compact('page', 'post', 'currencies', 'countries', 'interests'));
    }

    /**
     * Sauvegarde (brouillon ou soumission directe)
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'post_id'        => 'required|string',
            'page_id'        => 'required|string',
            'post_url'       => 'nullable|url|max:2000',
            'post_thumbnail' => 'nullable|url|max:2000',
            'post_message'   => 'nullable|string|max:5000',
            // Tolérance d'1 jour pour absorber les décalages horaires serveur/client.
            'start_date'     => 'required|date|after_or_equal:' . now()->subDay()->format('Y-m-d'),
            'end_date'       => 'required|date|after:start_date',
            'budget'         => 'required|numeric|min:1000',
            'currency'       => 'required|in:XOF,EUR,USD',
            'sensitivity'    => 'required|in:faible,moyenne,elevee',
            'whatsapp_url'   => ['nullable', 'url', 'max:500', 'regex:/^https?:\/\//i'],
            'target'              => 'required|array',
            'target.age_min'      => 'required|integer|min:13|max:65',
            'target.age_max'      => 'required|integer|min:13|max:65|gte:target.age_min',
            'target.gender'       => 'required|in:all,male,female',
            'target.countries'    => 'required|array|min:1',
            'target.interests'    => 'nullable|array',
        ]);

        $page   = FacebookPage::where('page_id', $validated['page_id'])->firstOrFail();
        $action = $request->input('action', 'draft');

        // Vérification de boostabilité (PDF Architecture — règle stricte)
        // Un post non boostable ne peut pas être soumis (brouillon OK, soumission bloquée)
        $postMaster = FacebookPost::where('post_id', $validated['post_id'])->first();
        if ($action === 'submit' && $postMaster && !$postMaster->isBoostable()) {
            return back()
                ->withInput()
                ->with('error', 'Ce post ne peut pas être boosté : ' . $postMaster->boostability_reason);
        }

        $boost = BoostRequest::create([
            'post_id'        => $validated['post_id'],
            'post_master_id' => $postMaster?->id,
            'page_id'        => $validated['page_id'],
            'page_name'      => $page->page_name,
            'post_url'       => $validated['post_url'],
            'post_thumbnail' => $validated['post_thumbnail'],
            'post_message'   => $validated['post_message'],
            'start_date'     => $validated['start_date'],
            'end_date'       => $validated['end_date'],
            'budget'         => $validated['budget'],
            'currency'       => $validated['currency'],
            'sensitivity'    => $validated['sensitivity'],
            'whatsapp_url'   => $validated['whatsapp_url'],
            'target'         => $validated['target'],
            'status'         => 'draft',
            'operator_id'    => auth()->id(),
        ]);

        if ($action === 'submit') {
            $boost->update(['status' => 'pending_n1']);
            $this->notifyN1Validators($boost);

            return redirect()->route('boost.my-requests')
                             ->with('success', 'Demande soumise pour validation N+1. Les validateurs ont été notifiés.');
        }

        return redirect()->route('boost.show', $boost->id)
                         ->with('success', 'Brouillon sauvegardé avec succès.');
    }

    /**
     * Soumettre un brouillon ou un rejeté pour validation
     */
    public function submit(Request $request, BoostRequest $boost)
    {
        abort_if($boost->operator_id !== auth()->id(), 403);
        abort_if(!in_array($boost->status, ['draft', 'rejected_n1', 'rejected_n2']), 422, 'Ce boost ne peut pas être soumis.');

        // Vérification de boostabilité au moment de la soumission
        $postMaster = FacebookPost::where('post_id', $boost->post_id)->first();
        if ($postMaster && !$postMaster->isBoostable()) {
            return redirect()->route('boost.show', $boost->id)
                ->with('error', 'Ce post ne peut plus être boosté : ' . $postMaster->boostability_reason);
        }

        $boost->update(['status' => 'pending_n1']);
        $this->notifyN1Validators($boost);

        return redirect()->route('boost.my-requests')
                         ->with('success', 'Demande soumise pour validation N+1. Les validateurs ont été notifiés.');
    }

    /**
     * Mes campagnes (opérateur)
     */
    public function myRequests(Request $request)
    {
        $query = BoostCampaign::where('user_id', auth()->id());

        if ($request->status === 'pending') {
            $query->whereIn('execution_status', ['pending_n1', 'pending_n2']);
        } elseif ($request->status === 'error') {
            $query->whereIn('execution_status', ['error', 'rejected']);
        } elseif ($request->status) {
            $query->where('execution_status', $request->status);
        }

        $campaigns = $query->latest()->paginate(10)->withQueryString();

        return view('boost.my-requests', compact('campaigns'));
    }

    /**
     * Détail d'un boost
     * Opérateur : accès à ses propres boosts uniquement.
     * Validateurs / admin : accès à tous.
     */
    public function show(BoostRequest $boost)
    {
        $user = auth()->user();
        if (!$user->hasRole(['validator_n1', 'validator_n2', 'validator', 'admin'])) {
            abort_if($boost->operator_id !== $user->id, 403, 'Accès non autorisé à ce boost.');
        }

        $boost->load('approvals.user');
        return view('boost.show', compact('boost'));
    }

    // ─────────────────────────────────────────────────────────

    private function notifyN1Validators(BoostRequest $boost): void
    {
        // Résoudre l'ID DB de la page à partir du page_id Facebook (string)
        $boostFbPageDbId = \App\Models\FacebookPage::where('page_id', $boost->page_id)->value('id');

        $n1Users = User::role(['validator_n1', 'validator'])
            ->when($boostFbPageDbId, fn($q) => $q->whereHas('facebookPages', fn($q2) => $q2->where('facebook_pages.id', $boostFbPageDbId)))
            ->get();

        $n1Users->each(fn($u) => $u->notify(new BoostSubmittedNotification($boost)));
        User::role('admin')->get()->each(fn($a) => $a->notify(new BoostSubmittedNotification($boost)));
    }

    // ─────────────────────────────────────────────────────────
    // Données de référence
    // ─────────────────────────────────────────────────────────

    private function getAfricanCountries(): array
    {
        return [
            'CI' => "Côte d'Ivoire",
            'SN' => 'Sénégal',
            'ML' => 'Mali',
            'BF' => 'Burkina Faso',
            'GN' => 'Guinée',
            'CM' => 'Cameroun',
            'BJ' => 'Bénin',
            'TG' => 'Togo',
            'NE' => 'Niger',
            'GH' => 'Ghana',
            'NG' => 'Nigeria',
            'MA' => 'Maroc',
            'DZ' => 'Algérie',
            'TN' => 'Tunisie',
            'EG' => 'Égypte',
            'CD' => 'RD Congo',
            'MG' => 'Madagascar',
            'MZ' => 'Mozambique',
            'KE' => 'Kenya',
            'TZ' => 'Tanzanie',
            'ZA' => 'Afrique du Sud',
            'FR' => 'France',
            'BE' => 'Belgique',
            'CH' => 'Suisse',
            'CA' => 'Canada',
            'US' => 'États-Unis',
        ];
    }

    private function getInterestsList(): array
    {
        return [
            'Football', 'Basketball', 'Musique', 'Cinéma', 'Mode',
            'Cuisine', 'Voyage', 'Technologie', 'Business', 'Automobile',
            'Bière', 'Boissons', 'Télécommunications', 'E-commerce',
            'Santé', 'Beauté', 'Gaming', 'Actualités', 'Politique',
            'Famille', 'Jeunesse', 'Éducation', 'Religion', 'Luxe',
        ];
    }
}
