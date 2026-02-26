<?php

namespace App\Http\Controllers;

use App\Models\BoostRequest;
use App\Models\FacebookPage;
use App\Services\MetaPostService;
use Illuminate\Http\Request;
use App\Notifications\BoostSubmittedNotification;
use App\Models\User;

class BoostController extends Controller
{
    public function __construct(private MetaPostService $metaService) {}

    /**
     * Formulaire de création
     */
    public function create(Request $request)
    {
        $pageId  = $request->get('page_id');
        $postId  = $request->get('post_id');
        $page    = FacebookPage::where('page_id', $pageId)->firstOrFail();

        $posts  = $this->metaService->getPagePosts($pageId, 25);
        $post   = collect($posts['data'])->firstWhere('id', $postId);

        if (!$post) {
            return redirect()->route('posts.index')->with('error', 'Post introuvable.');
        }

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
            'post_url'       => 'nullable|string',
            'post_thumbnail' => 'nullable|string',
            'post_message'   => 'nullable|string',
            'start_date'     => 'required|date|after_or_equal:today',
            'end_date'       => 'required|date|after:start_date',
            'budget'         => 'required|numeric|min:1000',
            'currency'       => 'required|in:XOF,EUR,USD',
            'sensitivity'    => 'required|in:faible,moyenne,elevee',
            'whatsapp_url'   => 'nullable|string|max:500',
            'target'              => 'required|array',
            'target.age_min'      => 'required|integer|min:13|max:65',
            'target.age_max'      => 'required|integer|min:13|max:65|gte:target.age_min',
            'target.gender'       => 'required|in:all,male,female',
            'target.countries'    => 'required|array|min:1',
            'target.interests'    => 'nullable|array',
        ]);

        $page   = FacebookPage::where('page_id', $validated['page_id'])->firstOrFail();
        $action = $request->input('action', 'draft');

        $boost = BoostRequest::create([
            'post_id'        => $validated['post_id'],
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

        $boost->update(['status' => 'pending_n1']);
        $this->notifyN1Validators($boost);

        return redirect()->route('boost.my-requests')
                         ->with('success', 'Demande soumise pour validation N+1. Les validateurs ont été notifiés.');
    }

    /**
     * Mes demandes (opérateur)
     */
    public function myRequests(Request $request)
    {
        $query = BoostRequest::where('operator_id', auth()->id());

        // Filtre spécial : 'rejected' regroupe rejected_n1 + rejected_n2
        if ($request->status === 'rejected') {
            $query->whereIn('status', ['rejected_n1', 'rejected_n2']);
        } elseif ($request->status === 'pending') {
            $query->whereIn('status', ['pending_n1', 'pending_n2']);
        } elseif ($request->status) {
            $query->where('status', $request->status);
        }

        $boosts = $query->latest()->paginate(10);

        return view('boost.my-requests', compact('boosts'));
    }

    /**
     * Détail d'un boost
     */
    public function show(BoostRequest $boost)
    {
        $boost->load('approvals.user');
        return view('boost.show', compact('boost'));
    }

    // ─────────────────────────────────────────────────────────

    private function notifyN1Validators(BoostRequest $boost): void
    {
        $n1Users = User::role(['validator_n1', 'validator'])->get();
        foreach ($n1Users as $user) {
            $user->notify(new BoostSubmittedNotification($boost));
        }
        $admins = User::role('admin')->get();
        foreach ($admins as $admin) {
            $admin->notify(new BoostSubmittedNotification($boost));
        }
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
