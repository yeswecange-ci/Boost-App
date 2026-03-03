"""
Génère le guide de configuration PDF pour Boost Manager.
Usage : python generate_config_guide.py
"""

from fpdf import FPDF
from fpdf.enums import XPos, YPos
import datetime

# ─── Palette ───────────────────────────────────────────────
INDIGO   = (79,  70, 229)
INDIGO_L = (238, 242, 255)
RED      = (185,  28,  28)
RED_L    = (254, 226, 226)
GREEN    = (21, 128,  61)
GREEN_L  = (220, 252, 231)
AMBER    = (133,  77,  14)
AMBER_L  = (254, 249, 195)
SLATE_9  = (15,  23,  42)
SLATE_7  = (51,  65,  85)
SLATE_5  = (100, 116, 139)
SLATE_2  = (226, 232, 240)
WHITE    = (255, 255, 255)
GREY_BG  = (248, 250, 252)
DARK_BG  = (30,  41,  59)
CODE_FG  = (226, 232, 240)
CODE_CM  = (148, 163, 184)
CODE_GR  = (134, 239, 172)


class PDF(FPDF):

    def header(self):
        self.set_fill_color(*INDIGO)
        self.rect(0, 0, 210, 14, 'F')
        self.set_font('Helvetica', 'B', 9)
        self.set_text_color(*WHITE)
        self.set_xy(0, 3)
        self.cell(0, 8, 'BOOST MANAGER -- Guide de configuration', align='C')
        self.set_text_color(*SLATE_9)
        self.ln(10)

    def footer(self):
        self.set_y(-12)
        self.set_font('Helvetica', '', 7.5)
        self.set_text_color(*SLATE_5)
        self.set_fill_color(*SLATE_2)
        self.rect(0, self.get_y() - 2, 210, 14, 'F')
        self.cell(0, 10,
                  f'Boost Manager  |  Confidentiel  |  '
                  f'Genere le {datetime.date.today().strftime("%d/%m/%Y")}  |  Page {self.page_no()}',
                  align='C')

    # ── Helpers ────────────────────────────────────────────

    def section_title(self, num, title, color=INDIGO):
        self.ln(4)
        self.set_fill_color(*color)
        self.set_text_color(*WHITE)
        self.set_font('Helvetica', 'B', 9)
        # Pastille
        x0, y0 = 14, self.get_y()
        self.rect(x0, y0, 7, 7, 'F')
        self.set_xy(x0, y0)
        self.cell(7, 7, str(num), align='C')
        # Titre
        self.set_font('Helvetica', 'B', 12)
        self.set_text_color(*SLATE_9)
        self.set_xy(24, y0)
        self.cell(0, 7, title, new_x=XPos.LMARGIN, new_y=YPos.NEXT)
        # Ligne
        self.set_draw_color(*color)
        self.set_line_width(0.5)
        self.line(14, self.get_y(), 196, self.get_y())
        self.ln(3)

    def subsection(self, title):
        self.ln(2)
        self.set_font('Helvetica', 'B', 10)
        self.set_text_color(*INDIGO)
        self.set_x(14)
        self.cell(0, 6, '> ' + title, new_x=XPos.LMARGIN, new_y=YPos.NEXT)
        self.set_text_color(*SLATE_9)

    def body(self, text):
        self.set_font('Helvetica', '', 9.5)
        self.set_text_color(*SLATE_7)
        self.set_x(14)
        self.multi_cell(182, 5.5, text, new_x=XPos.LMARGIN, new_y=YPos.NEXT)
        self.ln(1)

    def alert_box(self, badge, label, text, fg, bg):
        self.ln(2)
        x, y = 14, self.get_y()
        # Estimer la hauteur
        self.set_font('Helvetica', '', 8.5)
        lines_est = max(1, len(text) // 88 + text.count('\n') + 1)
        h = 10 + lines_est * 5
        # Fond + bordure latérale
        self.set_fill_color(*bg)
        self.rect(x, y, 182, h, 'F')
        self.set_fill_color(*fg)
        self.rect(x, y, 3, h, 'F')
        self.set_draw_color(*fg)
        self.set_line_width(0.3)
        self.rect(x, y, 182, h, 'D')
        # En-tête
        self.set_xy(x + 6, y + 2)
        self.set_font('Helvetica', 'B', 9)
        self.set_text_color(*fg)
        self.cell(0, 5, f'[{badge}]  {label}')
        self.ln(5.5)
        self.set_x(x + 6)
        self.set_font('Helvetica', '', 8.5)
        self.set_text_color(*SLATE_7)
        self.multi_cell(172, 4.8, text, new_x=XPos.LMARGIN, new_y=YPos.NEXT)
        self.ln(4)

    def config_table(self, headers, rows, col_widths=None):
        self.ln(2)
        if not col_widths:
            col_widths = [60, 122]
        x = 14
        total_w = sum(col_widths)

        # En-tête
        self.set_fill_color(*INDIGO)
        self.set_text_color(*WHITE)
        self.set_font('Helvetica', 'B', 8.5)
        self.set_x(x)
        for i, h in enumerate(headers):
            self.cell(col_widths[i], 7, '  ' + h, border=0, fill=True)
        self.ln(7)

        # Lignes
        for idx, row in enumerate(rows):
            bg = GREY_BG if idx % 2 == 0 else WHITE
            row_y = self.get_y()

            # Calculer hauteur max
            max_h = 6
            for i, cell_text in enumerate(row):
                self.set_font('Helvetica', '', 8.5)
                nb = cell_text.count('\n') + 1
                lines_w = max(1, len(cell_text) // max(1, (col_widths[i] - 4) // 2))
                max_h = max(max_h, max(nb, lines_w) * 5)

            max_h = max(max_h, 7)

            # Fond
            self.set_fill_color(*bg)
            self.rect(x, row_y, total_w, max_h + 2, 'F')
            self.set_draw_color(*SLATE_2)
            self.set_line_width(0.2)
            self.rect(x, row_y, total_w, max_h + 2, 'D')

            # Contenu
            cur_x = x
            for i, cell_text in enumerate(row):
                self.set_xy(cur_x + 2, row_y + 1.5)
                if i == 0:
                    self.set_font('Helvetica', 'B', 8.5)
                    self.set_text_color(*INDIGO)
                else:
                    self.set_font('Helvetica', '', 8.5)
                    self.set_text_color(*SLATE_7)
                self.multi_cell(col_widths[i] - 2, 5, cell_text,
                                new_x=XPos.RIGHT, new_y=YPos.TOP)
                cur_x += col_widths[i]
            self.set_y(row_y + max_h + 2)

        self.set_text_color(*SLATE_9)
        self.ln(3)

    def code_block(self, code):
        self.ln(2)
        x, y = 14, self.get_y()
        lines = code.strip().split('\n')
        h = len(lines) * 5 + 6
        self.set_fill_color(*DARK_BG)
        self.rect(x, y, 182, h, 'F')
        self.set_font('Courier', '', 7.8)
        self.set_xy(x + 4, y + 3)
        for line in lines:
            if line.strip().startswith('#') or line.strip().startswith('--'):
                self.set_text_color(*CODE_CM)
            elif '<-' in line or 'obligatoire' in line.lower() or 'nouveau' in line.lower():
                self.set_text_color(*CODE_GR)
            else:
                self.set_text_color(*CODE_FG)
            self.cell(0, 5, line, new_x=XPos.LMARGIN, new_y=YPos.NEXT)
            self.set_x(x + 4)
        self.set_text_color(*SLATE_9)
        self.ln(4)

    def step_row(self, n, title, desc):
        self.set_x(14)
        # Numéro
        self.set_fill_color(*INDIGO)
        self.set_text_color(*WHITE)
        self.set_font('Helvetica', 'B', 9)
        self.cell(8, 8, str(n), fill=True, align='C')
        # Titre
        self.set_fill_color(*GREY_BG)
        self.set_text_color(*SLATE_9)
        self.set_font('Helvetica', 'B', 9)
        self.cell(174, 8, '  ' + title, fill=True)
        self.ln(8)
        # Description
        self.set_x(22)
        self.set_font('Helvetica', '', 8.5)
        self.set_text_color(*SLATE_5)
        self.multi_cell(174, 5, '   ' + desc, new_x=XPos.LMARGIN, new_y=YPos.NEXT)
        self.ln(1)
        self.set_text_color(*SLATE_9)


# ══════════════════════════════════════════════════════════════
#  GÉNÉRATION DU PDF
# ══════════════════════════════════════════════════════════════

pdf = PDF()
pdf.set_auto_page_break(auto=True, margin=18)
pdf.set_margins(14, 18, 14)
pdf.add_page()

# ── PAGE DE TITRE ────────────────────────────────────────────
pdf.set_fill_color(*INDIGO)
pdf.rect(0, 14, 210, 62, 'F')

pdf.set_font('Helvetica', 'B', 28)
pdf.set_text_color(*WHITE)
pdf.set_xy(0, 26)
pdf.cell(210, 13, 'BOOST MANAGER', align='C')

pdf.set_font('Helvetica', '', 13)
pdf.set_xy(0, 43)
pdf.cell(210, 7, 'Guide de configuration -- Mise en production', align='C')

pdf.set_font('Helvetica', '', 9)
pdf.set_text_color(165, 180, 252)
pdf.set_xy(0, 54)
pdf.cell(210, 6,
         f'Architecture SCD2 + Meta Ads API + N8N  |  '
         f'{datetime.date.today().strftime("%d/%m/%Y")}',
         align='C')

pdf.ln(52)
pdf.set_text_color(*SLATE_9)

# Bloc résumé
pdf.set_fill_color(*INDIGO_L)
pdf.set_draw_color(*INDIGO)
pdf.set_line_width(0.3)
pdf.rect(14, pdf.get_y(), 182, 28, 'FD')
pdf.set_xy(18, pdf.get_y() + 4)
pdf.set_font('Helvetica', 'B', 10)
pdf.set_text_color(*INDIGO)
pdf.cell(0, 6, 'Objectif de ce document')
pdf.ln(7)
pdf.set_x(18)
pdf.set_font('Helvetica', '', 9)
pdf.set_text_color(*SLATE_7)
pdf.multi_cell(174, 5.5,
    "Ce guide liste toutes les configurations necessaires pour faire fonctionner "
    "Boost Manager en production, conformement a l'Architecture Technique PDF "
    "(SCD2 + Meta Ads API + N8N). Suivre les etapes dans l'ordre indique.")
pdf.ln(10)
pdf.set_text_color(*SLATE_9)

# Légende
pdf.set_font('Helvetica', 'B', 9)
pdf.set_text_color(*SLATE_7)
pdf.set_x(14)
pdf.cell(30, 7, 'Legende :')
for label, fg, bg in [
    ('BLOQUANT -- requis', RED, RED_L),
    ('IMPORTANT -- recommande', AMBER, AMBER_L),
    ('GUIDE -- ordre de mise en route', GREEN, GREEN_L),
]:
    pdf.set_fill_color(*bg)
    pdf.set_text_color(*fg)
    pdf.set_font('Helvetica', 'B', 7.5)
    w = pdf.get_string_width(label) + 8
    pdf.cell(w, 7, '  ' + label, fill=True)
    pdf.cell(4, 7, '')
pdf.ln(10)
pdf.set_text_color(*SLATE_9)

# Sommaire
pdf.set_font('Helvetica', 'B', 11)
pdf.set_x(14)
pdf.cell(0, 7, 'Sommaire', new_x=XPos.LMARGIN, new_y=YPos.NEXT)
pdf.set_draw_color(*SLATE_2)
pdf.set_line_width(0.3)
pdf.line(14, pdf.get_y(), 196, pdf.get_y())
pdf.ln(3)

toc = [
    ('1', 'Pages Facebook en base de donnees',          'BLOQUANT',  RED),
    ('2', 'Configuration Meta API (/settings)',          'BLOQUANT',  RED),
    ('3', 'Configuration N8N (/settings)',               'BLOQUANT',  RED),
    ('4', 'Mise a jour du workflow N8N',                 'BLOQUANT',  RED),
    ('5', 'Callbacks webhook N8N -> Laravel',            'BLOQUANT',  RED),
    ('6', 'Variables .env a verifier',                   'IMPORTANT', AMBER),
    ('7', 'Worker de queue (notifications email)',        'IMPORTANT', AMBER),
    ('8', 'Premiere synchro et verification monitoring', 'IMPORTANT', AMBER),
    ('9', 'Ordre de mise en route recommande',           'GUIDE',     GREEN),
]
for num, title, badge, color in toc:
    pdf.set_x(14)
    pdf.set_font('Helvetica', '', 9.5)
    pdf.set_text_color(*SLATE_7)
    pdf.cell(8, 7, num + '.', align='R')
    pdf.set_text_color(*SLATE_9)
    pdf.cell(116, 7, '  ' + title)
    pdf.set_fill_color(*(RED_L if color == RED else AMBER_L if color == AMBER else GREEN_L))
    pdf.set_text_color(*color)
    pdf.set_font('Helvetica', 'B', 7.5)
    w = pdf.get_string_width(badge) + 6
    pdf.cell(w, 7, badge, fill=True, align='C')
    pdf.ln(7)
pdf.set_text_color(*SLATE_9)

# ══════════════════════════════════════════════════════════════
#  PAGE 2
# ══════════════════════════════════════════════════════════════
pdf.add_page()

# ── SECTION 1 ────────────────────────────────────────────────
pdf.section_title(1, 'Pages Facebook en base de donnees', RED)

pdf.alert_box('!', 'BLOQUANT',
    "Sans ces donnees, le service N8nWebhookService leve une RuntimeException des qu'un boost est approuve. "
    "Le champ ad_account_id est obligatoire pour l'appel Meta Ads API.",
    RED, RED_L)

pdf.body("La table facebook_pages doit etre renseignee avec les informations de chaque page Facebook geree.")

pdf.config_table(
    ['Colonne', 'Valeur attendue / Comment l\'obtenir'],
    [
        ['page_id',              "ID numerique de la page Facebook (ex: 123456789)\n"
                                 "-> Visible dans l'URL facebook.com/pg/{page_id} ou Business Manager"],
        ['page_name',            'Nom affiche de la page (ex: "Bracongo CI")'],
        ['ad_account_id',        "Format act_XXXXXXXXXX\n"
                                 "-> Meta Business Manager -> Comptes publicitaires -> Parametres"],
        ['access_token',         "Page Access Token (long-lived, 60 jours)\n"
                                 "-> Graph API Explorer -> choisir la page -> generer le token"],
        ['instagram_account_id', 'Optionnel -- ID du compte Instagram lie a la page'],
        ['is_active',            '1 pour activer la page dans l\'app'],
    ],
    [70, 112]
)

pdf.subsection('Exemple SQL d\'insertion')
pdf.code_block(
    "INSERT INTO facebook_pages\n"
    "  (page_id, page_name, ad_account_id, access_token, is_active, created_at, updated_at)\n"
    "VALUES (\n"
    "  '123456789',             -- <- ID Facebook de la page\n"
    "  'Nom de votre page',\n"
    "  'act_987654321012345',   -- <- Format obligatoire : act_XXXXXXXXXX\n"
    "  'EAA...',                -- <- Page Access Token long-lived\n"
    "  1,\n"
    "  NOW(), NOW()\n"
    ");"
)

pdf.alert_box('i', 'Comment obtenir un Page Access Token long-lived',
    "1. Creer une app Meta dans Meta Developers (developers.facebook.com)\n"
    "2. Activer les produits : Facebook Login + Marketing API\n"
    "3. Dans Graph API Explorer : selectionner l'app -> generer User Token\n"
    "4. Ajouter les permissions : pages_read_engagement, ads_management, ads_read, pages_manage_posts\n"
    "5. GET /me/accounts -> recuperer le access_token de la page (deja long-lived)\n"
    "6. Pour long-lived user token : GET /oauth/access_token?grant_type=fb_exchange_token",
    AMBER, AMBER_L)

# ── SECTION 2 ────────────────────────────────────────────────
pdf.section_title(2, 'Configuration Meta API  (/settings -> onglet Meta)', RED)

pdf.alert_box('!', 'BLOQUANT',
    "Sans le access_token Meta configure, la synchronisation des posts Facebook echoue. "
    "L'app affiche une erreur dans /posts et aucun post ne sera charge.",
    RED, RED_L)

pdf.body("Acceder a l'interface d'administration : /settings -> onglet 'Meta API'")

pdf.config_table(
    ['Parametre', 'Valeur / Description'],
    [
        ['meta.access_token',  "User Access Token long-lived OU System User Token\n"
                               "(scopes requis : pages_read_engagement, ads_management, ads_read)"],
        ['meta.app_id',        "ID de votre application Meta Developers"],
        ['meta.app_secret',    "Cle secrete de l'application Meta"],
        ['meta.api_version',   "v21.0 (valeur par defaut -- ne pas modifier sauf deprecation Meta)"],
        ['meta.mock_mode',     "false en production  |  true pour tester sans appel API reel"],
    ],
    [65, 117]
)

# ══════════════════════════════════════════════════════════════
#  PAGE 3
# ══════════════════════════════════════════════════════════════
pdf.add_page()

# ── SECTION 3 ────────────────────────────────────────────────
pdf.section_title(3, 'Configuration N8N  (/settings -> onglet N8N)', RED)

pdf.alert_box('!', 'BLOQUANT',
    "Sans les URL de webhooks N8N configurees, les boosts approuves restent bloques "
    "a l'etat 'creating' indefiniment et aucune campagne Meta n'est creee.",
    RED, RED_L)

pdf.config_table(
    ['Parametre', 'Valeur / Description'],
    [
        ['n8n.webhook_create',   "URL complete du webhook N8N de creation de campagne\n"
                                 "(ex: https://mon-n8n.example.com/webhook/boost-create)\n"
                                 "N8N doit repondre 200 OK a la reception"],
        ['n8n.webhook_activate', "URL webhook pour l'activation d'une campagne en pause\n"
                                 "(ex: https://mon-n8n.example.com/webhook/boost-activate)"],
        ['n8n.webhook_pause',    "URL webhook pour la mise en pause d'une campagne active\n"
                                 "(ex: https://mon-n8n.example.com/webhook/boost-pause)"],
        ['n8n.secret',           "Cle secrete partagee entre l'app et N8N\n"
                                 "L'app envoie ce secret dans le header X-N8N-Secret\n"
                                 "N8N doit l'envoyer en retour dans les callbacks"],
        ['n8n.timeout',          "10 (secondes) -- delai max d'attente de reponse N8N\n"
                                 "Augmenter si N8N est lent au demarrage (ex: 30)"],
        ['n8n.mock_mode',        "false en production  |  true pour simuler N8N localement"],
    ],
    [65, 117]
)

# ── SECTION 4 ────────────────────────────────────────────────
pdf.section_title(4, 'Mise a jour du workflow N8N  (payload modifie)', RED)

pdf.alert_box('!', 'BLOQUANT -- BREAKING CHANGE',
    "Le payload envoye par l'app a change suite a l'implementation de l'architecture PDF. "
    "Le workflow N8N DOIT etre mis a jour pour lire les nouveaux champs, "
    "sinon les campagnes Meta Ads seront creees avec des parametres incorrects.",
    RED, RED_L)

pdf.subsection('Champs modifies / ajoutes dans le payload N8N')
pdf.config_table(
    ['Champ', 'Avant', 'Apres (nouveau)'],
    [
        ['lifetime_budget',                 '5.0 (unite EUR)',    '500 (centimes) -- 5 EUR = 500'],
        ['is_adset_budget_sharing_enabled', 'absent',             'false (ABO -- obligatoire Meta)'],
        ['special_ad_categories',           'absent',             '[] (tableau vide -- obligatoire)'],
        ['object_story_id',                 'absent',             '"{page_id}_{post_id}" (boost post)'],
        ['targeting.geo_locations.cities',  'absent',             '[{"key":"102057199141875"}] Abidjan'],
        ['destination_type',                'absent',             '"WHATSAPP" si whatsapp_url renseigne'],
        ['genders',                         'gender: "all"',      '[] all | [1] homme | [2] femme'],
    ],
    [65, 55, 62]
)

pdf.subsection('Exemple complet du payload recu par N8N')
pdf.code_block(
    '{\n'
    '  "boost_id": 42,\n'
    '  "callback_url": "https://votre-app.com/webhook/n8n/boost-created",\n'
    '  "mode": "CREATE_PAUSED",\n'
    '  "ad_account_id": "act_987654321012345",\n'
    '  "post_id": "123456789_987654321",\n'
    '  "page_id": "123456789",\n'
    '  "objective": "OUTCOME_TRAFFIC",\n'
    '  "campaign_status": "PAUSED",\n'
    '  "special_ad_categories": [],          <- NOUVEAU -- obligatoire Meta\n'
    '  "lifetime_budget": 50000,             <- MODIFIE -- en centimes (500 EUR)\n'
    '  "currency": "XOF",\n'
    '  "billing_event": "IMPRESSIONS",\n'
    '  "optimization_goal": "LINK_CLICKS",\n'
    '  "is_adset_budget_sharing_enabled": false,  <- NOUVEAU -- ABO\n'
    '  "destination_type": "WHATSAPP",       <- NOUVEAU\n'
    '  "object_story_id": "123456789_987654321",  <- NOUVEAU -- boost post\n'
    '  "targeting": {\n'
    '    "age_min": 18, "age_max": 45,\n'
    '    "genders": [],\n'
    '    "geo_locations": {\n'
    '      "countries": ["CI"],\n'
    '      "cities": [{"key": "102057199141875"}]  <- NOUVEAU -- Abidjan\n'
    '    },\n'
    '    "interests": [{"name": "Football"}, {"name": "Business"}]\n'
    '  }\n'
    '}'
)

# ── SECTION 5 ────────────────────────────────────────────────
pdf.section_title(5, 'Callbacks webhook N8N -> Laravel', RED)

pdf.alert_box('!', 'BLOQUANT',
    "Sans ces callbacks, les boosts restent bloques a l'etat 'creating' apres l'appel N8N. "
    "L'app ne saura jamais si la campagne a ete creee ou activee sur Meta. "
    "L'app doit etre accessible depuis le reseau N8N (URL publique ou meme reseau prive).",
    RED, RED_L)

pdf.config_table(
    ['Evenement', 'URL a appeler depuis N8N', 'Header requis'],
    [
        ['Campagne creee (PAUSED)',
         'POST {APP_URL}/webhook/n8n/boost-created',
         'X-N8N-Secret: {n8n.secret}'],
        ['Campagne activee / statut mis a jour',
         'POST {APP_URL}/webhook/n8n/boost-activated',
         'X-N8N-Secret: {n8n.secret}'],
    ],
    [42, 90, 50]
)

pdf.subsection('Payload attendu -- boost-created')
pdf.code_block(
    '{\n'
    '  "boost_id": 42,\n'
    '  "meta_campaign_id": "120210001234567",\n'
    '  "meta_adset_id":    "120210009876543",\n'
    '  "meta_ad_id":       "120210005555555",\n'
    '  "error": null    -- ou message d\'erreur si echec Meta Ads\n'
    '}'
)

pdf.subsection('Payload attendu -- boost-activated')
pdf.code_block(
    '{\n'
    '  "boost_id": 42,\n'
    '  "status": "active",  -- "active" | "paused" | "failed"\n'
    '  "error": null\n'
    '}'
)

# ══════════════════════════════════════════════════════════════
#  PAGE 4
# ══════════════════════════════════════════════════════════════
pdf.add_page()

# ── SECTION 6 ────────────────────────────────────────────────
pdf.section_title(6, 'Variables .env a verifier', AMBER)

pdf.alert_box('!', 'IMPORTANT',
    "Ces variables conditionnent le bon fonctionnement des notifications, "
    "des callbacks et de l'acces depuis l'exterieur (N8N).",
    AMBER, AMBER_L)

pdf.config_table(
    ['Variable', 'Valeur attendue en production'],
    [
        ['APP_URL',          "URL publique du serveur (ex: https://boost.monentreprise.com)\n"
                             "Critique : utilisee pour generer les callback_url envoyees a N8N"],
        ['APP_ENV',          'production'],
        ['APP_DEBUG',        'false  (ne jamais laisser a true en production)'],
        ['QUEUE_CONNECTION', 'database  -- les notifications email passent par la queue'],
        ['MAIL_MAILER',      'smtp  (ou sendmail / mailgun / ses selon votre infra)'],
        ['MAIL_HOST',        'Adresse du serveur SMTP'],
        ['MAIL_PORT',        '587 (TLS) ou 465 (SSL)'],
        ['MAIL_USERNAME',    'Identifiant SMTP'],
        ['MAIL_PASSWORD',    'Mot de passe SMTP'],
        ['MAIL_FROM_ADDRESS','Adresse expediteur (ex: noreply@boost.monentreprise.com)'],
        ['MAIL_FROM_NAME',   '"Boost Manager"'],
    ],
    [60, 122]
)

pdf.subsection('Exemple .env production (section email)')
pdf.code_block(
    "APP_URL=https://boost.monentreprise.com\n"
    "APP_ENV=production\n"
    "APP_DEBUG=false\n"
    "\n"
    "QUEUE_CONNECTION=database\n"
    "\n"
    "MAIL_MAILER=smtp\n"
    "MAIL_HOST=smtp.gmail.com\n"
    "MAIL_PORT=587\n"
    "MAIL_ENCRYPTION=tls\n"
    "MAIL_USERNAME=votre@email.com\n"
    "MAIL_PASSWORD=votre_mot_de_passe\n"
    "MAIL_FROM_ADDRESS=noreply@boost.monentreprise.com\n"
    'MAIL_FROM_NAME="Boost Manager"'
)

# ── SECTION 7 ────────────────────────────────────────────────
pdf.section_title(7, 'Worker de queue (notifications email)', AMBER)

pdf.alert_box('!', 'IMPORTANT',
    "Toutes les notifications (email + base de donnees) passent par la queue Laravel. "
    "Sans worker actif, les emails de validation, de rejet et d'activation ne sont JAMAIS envoyes. "
    "Les notifications in-app (icone cloche) fonctionnent quand meme sans worker.",
    AMBER, AMBER_L)

pdf.subsection('Lancer le worker manuellement (test)')
pdf.code_block(
    "cd /var/www/boost-manager\n"
    "php artisan queue:work --sleep=3 --tries=3 --max-time=3600"
)

pdf.subsection('Configuration Supervisor (production Linux)')
pdf.code_block(
    "# /etc/supervisor/conf.d/boost-worker.conf\n"
    "[program:boost-worker]\n"
    "process_name=%(program_name)s_%(process_num)02d\n"
    "command=php /var/www/boost-manager/artisan queue:work --sleep=3 --tries=3 --max-time=3600\n"
    "autostart=true\n"
    "autorestart=true\n"
    "user=www-data\n"
    "numprocs=1\n"
    "redirect_stderr=true\n"
    "stdout_logfile=/var/log/boost-worker.log\n"
    "\n"
    "# Activer :\n"
    "supervisorctl reread && supervisorctl update && supervisorctl start boost-worker:*"
)

pdf.subsection('Verifier les jobs en attente (SQL)')
pdf.code_block(
    "-- Jobs en queue\n"
    "SELECT id, queue, attempts, created_at FROM jobs ORDER BY created_at DESC LIMIT 20;\n"
    "\n"
    "-- Jobs echoues\n"
    "SELECT id, queue, exception, failed_at FROM failed_jobs ORDER BY failed_at DESC;"
)

# ── SECTION 8 ────────────────────────────────────────────────
pdf.section_title(8, 'Premiere synchro et verification du monitoring', AMBER)

pdf.body(
    "Apres avoir configure Meta API et les pages Facebook, "
    "declencher une premiere synchronisation pour valider que tout fonctionne."
)

for n, step in enumerate([
    ("Se connecter avec un compte admin ou validator",
     ""),
    ("Aller sur /posts -> selectionner une page Facebook configuree",
     "La synchro se declenche automatiquement, un sync_run est cree"),
    ("Verifier que les posts s'affichent avec les badges verts 'Boostable'",
     "Si fb_status != FB_OK, le post apparait en rouge avec la raison"),
    ("Aller sur /sync-runs -> verifier qu'un run FINISHED apparait",
     "Si FAILED : cliquer sur le run -> voir les erreurs dans sync_errors"),
    ("Tenter de booster un post (mock_mode=true d'abord)",
     "Creer -> soumettre -> valider -> verifier etat paused_ready -> activer"),
    ("Passer mock_mode=false et tester avec un vrai post",
     "Verifier que N8N recoit le payload et rappelle /webhook/n8n/boost-created"),
], start=1):
    pdf.step_row(n, step[0], step[1] if step[1] else "Etape prerequise.")

pdf.subsection('Verification SQL de la structure SCD2')
pdf.code_block(
    "-- Posts master avec statuts de boostabilite\n"
    "SELECT post_id, fb_status, business_status, is_boostable, last_synced_at\n"
    "FROM posts_master ORDER BY last_synced_at DESC LIMIT 10;\n"
    "\n"
    "-- Historique SCD2 d'un post (versions)\n"
    "SELECT ph.id, ph.row_hash, ph.is_active, ph.valid_from, ph.valid_to\n"
    "FROM posts_history ph\n"
    "JOIN posts_master pm ON pm.id = ph.post_master_id\n"
    "WHERE pm.post_id = 'VOTRE_POST_ID'\n"
    "ORDER BY ph.valid_from DESC;\n"
    "\n"
    "-- Audit des boosts avec entites Meta\n"
    "SELECT br.id, br.status, ae.campaign_id, ae.adset_id, ae.ad_id\n"
    "FROM boost_runs br\n"
    "LEFT JOIN ads_entities ae ON ae.boost_run_id = br.id\n"
    "ORDER BY br.created_at DESC LIMIT 10;"
)

# ── SECTION 9 ────────────────────────────────────────────────
pdf.section_title(9, 'Ordre de mise en route recommande', GREEN)

pdf.alert_box('OK', 'Suivre cet ordre pour eviter les erreurs de dependance',
    "Chaque etape depend de la precedente. Ne pas sauter d'etape.",
    GREEN, GREEN_L)

steps_final = [
    ("Inserer les pages Facebook en BDD",
     "Table facebook_pages -- champs : page_id, ad_account_id (act_XXX), access_token, is_active=1"),
    ("Configurer /settings -> Meta",
     "access_token long-lived + app_id + app_secret + api_version=v21.0 + mock_mode=false"),
    ("Tester la synchro",
     "Ouvrir /posts -> verifier posts charges + /sync-runs -> run FINISHED sans erreurs"),
    ("Configurer /settings -> N8N",
     "webhook_create + webhook_activate + webhook_pause + secret partage + mock_mode=false"),
    ("Mettre a jour le workflow N8N",
     "Adapter les noeuds pour lire les nouveaux champs (budget centimes, object_story_id, etc.)"),
    ("Configurer les callbacks N8N",
     "N8N doit POST vers /webhook/n8n/boost-created et /boost-activated avec X-N8N-Secret"),
    ("Configurer le .env production",
     "APP_URL public + MAIL_* + APP_DEBUG=false + APP_ENV=production"),
    ("Lancer le queue worker",
     "php artisan queue:work (ou Supervisor en production) pour les notifications email"),
    ("Test bout-en-bout mock_mode=true",
     "Creer boost -> soumettre -> valider N1 -> verifier paused_ready -> activer -> verifier active"),
    ("Passer en production",
     "mock_mode=false sur N8N et Meta -> tester avec un vrai post et un petit budget"),
]
for n, (title, desc) in enumerate(steps_final, 1):
    pdf.step_row(n, title, desc)

# Note finale
pdf.ln(4)
pdf.set_fill_color(*INDIGO_L)
pdf.set_draw_color(*INDIGO)
pdf.set_line_width(0.3)
y = pdf.get_y()
pdf.rect(14, y, 182, 22, 'FD')
pdf.set_fill_color(*INDIGO)
pdf.rect(14, y, 3, 22, 'F')
pdf.set_xy(20, y + 3)
pdf.set_font('Helvetica', 'B', 9.5)
pdf.set_text_color(*INDIGO)
pdf.cell(0, 6, 'Ressources et acces')
pdf.ln(7)
pdf.set_x(20)
pdf.set_font('Helvetica', '', 8.5)
pdf.set_text_color(*SLATE_7)
pdf.multi_cell(170, 5,
    "Monitoring synchros : /sync-runs  |  Configuration : /settings  |  "
    "Meta Developers : developers.facebook.com  |  "
    "Meta Business Manager : business.facebook.com  |  "
    "Graph API Explorer : developers.facebook.com/tools/explorer")

# ── SAUVEGARDE ───────────────────────────────────────────────
output_path = r'C:\wamp64\www\YESWECANGE\boost-app\Guide_Configuration_Boost_Manager.pdf'
pdf.output(output_path)
print(f"PDF genere : {output_path}")
print(f"Pages : {pdf.page}")
