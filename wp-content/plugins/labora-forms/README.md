# Labora Forms

Contact Form 7 forms for the Labora site, with stricter validation than CF7's own.
Requires Contact Form 7 (6.x) and PHP 8.3. Leads are stored by **Database for Contact Form 7** (Contact > Database).

## Forms

Defined in code (`includes/class-forms.php`), so they are versioned and identical on every environment.

```bash
wp labora-forms setup                       # create or update the forms in CF7 (keeps Additional Settings edits)
wp labora-forms setup --overwrite-settings  # also reset Additional Settings to the defaults
```

| Key | CF7 title | Used on | Fields |
|---|---|---|---|
| `demo` | Book a demo | Homepage (`labora-child/template-parts/demo-form.php`) | full_name*, email*, company*, lab_type*, centers*, message |
| `contact` | Contact | Contact page (when it is built) | full_name*, email*, company*, phone, topic*, centers*, message, plan (hidden), modules (hidden) |

Markup matches the HTML site (`.form-grid`, `.field`, labels, ids), so the theme styles it; CF7-specific pieces
(error tips, spinner, response box) are styled in `labora-child/assets/css/child.css`.

In a template: `<?php echo labora_forms_render( 'demo' ); ?>` (CF7's CSS and JS load only on pages that render a form).

## No email is sent

Each form's **Additional Settings** contain `skip_mail: on`: the submission is validated and saved as a lead, and CF7
reports success, but no email goes out. To start sending, remove that line in Contact > Contact Forms > (form) >
Additional Settings. The Mail tab is already filled in (to the site admin email, Reply-To the visitor).

Other Additional Settings lines read by this plugin:

| Line | Effect |
|---|---|
| `labora_event: demo_request_submitted` | Event pushed to `dataLayer` after a successful send |
| `labora_thanks: /thank-you/` | After a successful send, wait 1.5 s, then open this page (empty: stay on the form) |
| `lead_guard_required_<field>: Message` | Message when that required field is empty (read by Lead Guard), e.g. `lead_guard_required_full_name: Enter your name.` |

## Validation

Comes from **Lead Guard for Contact Form 7** (`../lead-guard-cf7`, required by this plugin): phone normalization,
email checks, one submission per person per 24 hours (shown in the form's message box), and a honeypot added to every
form. Per-field required messages are the `lead_guard_required_<field>` lines in each form's Additional Settings.
