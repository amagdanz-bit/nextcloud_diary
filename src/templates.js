/**
 * Templates that can be inserted into an empty diary entry.
 *
 * Every template is plain Markdown, so entries written with a template stay
 * normal entries: editing, the entry list and the PDF/Markdown export work
 * exactly as before. To add a template, append an object with a unique `id`,
 * the `name` shown in the menu and the Markdown `content`.
 */
export default [
	{
		id: 'emotions',
		name: 'Gefühle wahrnehmen (3 Fragen)',
		content: [
			'## Was hat mich heute emotional berührt?',
			'> Ein Gespräch, ein Blick, eine Erinnerung …',
			'',
			'',
			'## Was habe ich dabei in meinem Körper gespürt?',
			'> Enge in der Brust, Wärme, Unruhe, Tränen …',
			'',
			'',
			'## Was hätte ich in diesem Moment gebraucht oder mir gewünscht?',
			'> Nähe, Sicherheit, Verständnis, etwas Zeit …',
			'',
			'',
			'*Du musst nicht jede Frage beantworten können. „Ich weiß es noch nicht“ ist ebenfalls eine ehrliche Antwort.*',
			'',
		].join('\n'),
	},
]
