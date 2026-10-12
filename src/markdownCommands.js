/**
 * Markdown formatting commands for the CodeMirror 6 editor.
 *
 * Every command takes the EditorView and returns true, so they can be used both
 * for the toolbar and in a CodeMirror keymap.
 */
import { EditorSelection } from '@codemirror/state'

/**
 * Wrap every selection in `marker` (e.g. ** for bold), or unwrap it if it is
 * already wrapped. An empty selection inserts the markers around the cursor.
 *
 * @param {string} marker The Markdown marker
 * @return {Function} CodeMirror command
 */
export function toggleWrap(marker) {
	return (view) => {
		const { state } = view
		const len = marker.length
		view.dispatch(state.changeByRange((range) => {
			const before = state.sliceDoc(range.from - len, range.from)
			const after = state.sliceDoc(range.to, range.to + len)
			if (before === marker && after === marker) {
				return {
					changes: [
						{ from: range.from - len, to: range.from },
						{ from: range.to, to: range.to + len },
					],
					range: EditorSelection.range(range.from - len, range.to - len),
				}
			}
			return {
				changes: [
					{ from: range.from, insert: marker },
					{ from: range.to, insert: marker },
				],
				range: EditorSelection.range(range.from + len, range.to + len),
			}
		}), { scrollIntoView: true, userEvent: 'input.format' })
		return true
	}
}

/**
 * Add a prefix to every line touched by the selection, or remove it if all of
 * those lines already have it.
 *
 * @param {RegExp} pattern Matches an existing prefix at the start of a line
 * @param {Function} prefixFor (index) => prefix to add for the n-th line
 * @return {Function} CodeMirror command
 */
function toggleLinePrefix(pattern, prefixFor) {
	return (view) => {
		const { state } = view
		const lines = []
		for (const range of state.selection.ranges) {
			const first = state.doc.lineAt(range.from).number
			const last = state.doc.lineAt(range.to).number
			for (let n = first; n <= last; n++) {
				const line = state.doc.line(n)
				if (!lines.some(l => l.number === n)) {
					lines.push(line)
				}
			}
		}
		const allPrefixed = lines.every(line => pattern.test(line.text))
		const changes = lines.map((line, index) => {
			const existing = line.text.match(pattern)
			if (allPrefixed) {
				return { from: line.from, to: line.from + existing[0].length }
			}
			// Replace another prefix of the same kind (e.g. "## " by "> ") instead of stacking them
			return { from: line.from, to: line.from + (existing ? existing[0].length : 0), insert: prefixFor(index) }
		})
		view.dispatch({ changes, scrollIntoView: true, userEvent: 'input.format' })
		return true
	}
}

export const toggleBold = toggleWrap('**')
export const toggleItalic = toggleWrap('*')
export const toggleStrikethrough = toggleWrap('~~')
export const toggleHeading = toggleLinePrefix(/^#{1,6} /, () => '## ')
export const toggleQuote = toggleLinePrefix(/^> ?/, () => '> ')
export const toggleBulletList = toggleLinePrefix(/^[-*+] /, () => '- ')
export const toggleNumberedList = toggleLinePrefix(/^\d+[.)] /, (index) => `${index + 1}. `)

/**
 * Turn the selection into a link and select the URL placeholder, so it can be
 * typed over directly.
 *
 * @param {object} view CodeMirror EditorView
 * @return {boolean}
 */
export function insertLink(view) {
	const { state } = view
	const url = 'https://'
	view.dispatch(state.changeByRange((range) => {
		const text = state.sliceDoc(range.from, range.to)
		const insert = `[${text}](${url})`
		const urlStart = range.from + text.length + 3
		return {
			changes: { from: range.from, to: range.to, insert },
			range: EditorSelection.range(urlStart, urlStart + url.length),
		}
	}), { scrollIntoView: true, userEvent: 'input.format' })
	return true
}
