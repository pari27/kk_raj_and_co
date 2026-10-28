<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EmailTemplate;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class EmailTemplateController extends Controller
{
    public function index(): View
    {
        return view('admin.settings.email-templates.index', [
            'templates' => EmailTemplate::query()->orderBy('id')->get(),
        ]);
    }

    public function show(EmailTemplate $template): View
    {
        return view('admin.settings.email-templates.show', compact('template'));
    }

    public function edit(EmailTemplate $template): View
    {
        return view('admin.settings.email-templates.edit', compact('template'));
    }

    public function update(Request $request, EmailTemplate $template): RedirectResponse
    {
        $validated = $request->validate([
            'subject' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string', 'max:10000'],
        ]);

        $allowed = array_keys($template->variables());
        $used = array_merge(
            EmailTemplate::placeholdersIn($validated['subject']),
            EmailTemplate::placeholdersIn($validated['body']),
        );

        $unknown = array_values(array_unique(array_diff($used, $allowed)));

        if ($unknown !== []) {
            throw ValidationException::withMessages([
                'body' => 'Unknown placeholder: '.implode(', ', array_map(fn ($name) => '{{'.$name.'}}', $unknown))
                    .'. Available: '.implode(', ', array_map(fn ($name) => '{{'.$name.'}}', $allowed)).'.',
            ]);
        }

        $missing = array_diff($template->requiredVariables(), EmailTemplate::placeholdersIn($validated['body']));

        if ($missing !== []) {
            throw ValidationException::withMessages([
                'body' => 'The body must keep '.implode(', ', array_map(fn ($name) => '{{'.$name.'}}', $missing)).', or the email will not work.',
            ]);
        }

        $changed = $template->subject !== $validated['subject'] || $template->body !== $validated['body'];

        $template->update($validated);

        if ($changed) {
            AuditLogger::log(
                action: 'Updated',
                module: 'Settings',
                recordLabel: 'Email template: '.$template->key,
                details: "Email template {$template->key} edited",
                subject: $template,
            );
        }

        return redirect()->route('admin.settings.email-templates.index');
    }
}
