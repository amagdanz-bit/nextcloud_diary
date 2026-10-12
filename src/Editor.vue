<template>
	<div id="diary-editor">
		<div id="entry-title">
			<span>
				<NcLoadingIcon v-if="isLoading" :size="20" class="title-loading" />
				{{ unSavedMarker }}{{ title }}
			</span>
			<NcActions v-if="canInsertTemplate"
				class="template-menu"
				:force-menu="true"
				:menu-title="t('diary', 'Use template')">
				<template #icon>
					<TextBoxEditOutline :size="20" />
				</template>
				<NcActionButton v-for="template in templates"
					:key="template.id"
					:close-after-click="true"
					@click="insertTemplate(template)">
					{{ template.name }}
				</NcActionButton>
			</NcActions>
		</div>
		<div class="editor-toolbar" role="toolbar" :aria-label="t('diary', 'Formatting')">
			<!-- mousedown.prevent keeps the focus (and the on-screen keyboard) in the editor -->
			<button v-for="button in toolbarButtons"
				:key="button.name"
				type="button"
				:class="{ separated: button.separated, active: button.name === 'preview' && preview }"
				:disabled="preview && !button.alwaysEnabled"
				:title="button.title"
				:aria-label="button.title"
				@mousedown.prevent
				@click="button.action">
				<component :is="button.icon" :size="20" />
			</button>
		</div>
		<div v-show="!preview" ref="editorHost" class="editor-host" />
		<!-- eslint-disable-next-line vue/no-v-html -- sanitized with DOMPurify -->
		<div v-if="preview" class="editor-preview markdown-body" v-html="previewHtml" />
		<div v-if="isLoading" id="overlay">
			<NcLoadingIcon :size="64" />
		</div>
	</div>
</template>
<script>
import { EditorState } from '@codemirror/state'
import { EditorView, keymap, placeholder } from '@codemirror/view'
import { defaultKeymap, history, historyKeymap } from '@codemirror/commands'
import { HighlightStyle, syntaxHighlighting } from '@codemirror/language'
import { markdown, markdownLanguage } from '@codemirror/lang-markdown'
import { tags } from '@lezer/highlight'
import { marked } from 'marked'
import DOMPurify from 'dompurify'

import { NcActions, NcActionButton, NcLoadingIcon } from '@nextcloud/vue'
import TextBoxEditOutline from 'vue-material-design-icons/TextBoxEditOutline'
import FormatBold from 'vue-material-design-icons/FormatBold'
import FormatItalic from 'vue-material-design-icons/FormatItalic'
import FormatStrikethrough from 'vue-material-design-icons/FormatStrikethrough'
import FormatHeaderPound from 'vue-material-design-icons/FormatHeaderPound'
import FormatQuoteClose from 'vue-material-design-icons/FormatQuoteClose'
import FormatListBulleted from 'vue-material-design-icons/FormatListBulleted'
import FormatListNumbered from 'vue-material-design-icons/FormatListNumbered'
import LinkVariant from 'vue-material-design-icons/LinkVariant'
import Eye from 'vue-material-design-icons/Eye'
import HelpCircleOutline from 'vue-material-design-icons/HelpCircleOutline'

import axios from '@nextcloud/axios'
import { generateUrl } from '@nextcloud/router'
import moment from '@nextcloud/moment'
import templates from './templates'
import {
	toggleBold,
	toggleItalic,
	toggleStrikethrough,
	toggleHeading,
	toggleQuote,
	toggleBulletList,
	toggleNumberedList,
	insertLink,
} from './markdownCommands'

const SAVE_DELAY = 500
const MARKDOWN_GUIDE_URL = 'https://www.markdownguide.org/basic-syntax/'

const highlightStyle = HighlightStyle.define([
	{ tag: tags.heading, fontWeight: 'bold', fontSize: '1.15em' },
	{ tag: tags.strong, fontWeight: 'bold' },
	{ tag: tags.emphasis, fontStyle: 'italic' },
	{ tag: tags.strikethrough, textDecoration: 'line-through' },
	{ tag: tags.quote, color: 'var(--color-text-maxcontrast)' },
	{ tag: [tags.link, tags.url], color: 'var(--color-primary-element)', textDecoration: 'underline' },
	{ tag: tags.monospace, fontFamily: 'var(--font-face-monospace, monospace)' },
	// Markdown syntax characters like #, *, > and list markers
	{ tag: tags.processingInstruction, color: 'var(--color-text-maxcontrast)' },
])

const editorTheme = EditorView.theme({
	'&': {
		color: 'var(--color-main-text)',
		backgroundColor: 'var(--color-main-background)',
		// 16px avoids the automatic zoom of mobile browsers when the editor gets the focus
		fontSize: '16px',
	},
	'&.cm-focused': { outline: 'none' },
	'.cm-scroller': { fontFamily: 'var(--font-face)', lineHeight: '1.6' },
	'.cm-content': {
		caretColor: 'var(--color-main-text)',
		padding: '8px 0 50px',
		// Large writing area: tapping anywhere below the text still puts the cursor into the entry
		minHeight: '50vh',
		// Nextcloud styles every contenteditable like an input field, the editor should look like a page
		border: 'none !important',
		borderRadius: '0 !important',
		boxShadow: 'none !important',
		outline: 'none !important',
		margin: '0 !important',
		width: '100%',
		backgroundColor: 'transparent !important',
	},
	'.cm-line': { padding: '0 4px' },
	'.cm-placeholder': { color: 'var(--color-text-maxcontrast)' },
})

export default {
	name: 'Editor',
	components: { NcActions, NcActionButton, NcLoadingIcon, TextBoxEditOutline },
	props: {
		date: {
			type: String,
			required: true,
		},
	},
	data() {
		return {
			status: null,
			unSavedChanges: false,
			content: '',
			preview: false,
			templates,
		}
	},
	computed: {
		title() {
			const day = moment(this.date)
			return day.format('dddd') + ' - ' + day.format('LL')
		},
		unSavedMarker() {
			return this.unSavedChanges ? '*' : ''
		},
		isLoading() {
			return this.status === 'loading'
		},
		canInsertTemplate() {
			// Only offer templates for an empty entry, so nothing that was written gets overwritten
			return this.templates.length > 0
				&& this.status === 'loaded'
				&& !this.preview
				&& this.content.trim() === ''
		},
		previewHtml() {
			return DOMPurify.sanitize(marked.parse(this.content))
		},
		toolbarButtons() {
			const format = (command) => () => {
				command(this.view)
				this.view.focus()
			}
			return [
				{ name: 'bold', icon: FormatBold, title: t('diary', 'Bold'), action: format(toggleBold) },
				{ name: 'italic', icon: FormatItalic, title: t('diary', 'Italic'), action: format(toggleItalic) },
				{ name: 'strikethrough', icon: FormatStrikethrough, title: t('diary', 'Strikethrough'), action: format(toggleStrikethrough) },
				{ name: 'heading', icon: FormatHeaderPound, title: t('diary', 'Heading'), action: format(toggleHeading) },
				{ name: 'quote', icon: FormatQuoteClose, title: t('diary', 'Quote'), action: format(toggleQuote), separated: true },
				{ name: 'bullet-list', icon: FormatListBulleted, title: t('diary', 'Bulleted list'), action: format(toggleBulletList) },
				{ name: 'numbered-list', icon: FormatListNumbered, title: t('diary', 'Numbered list'), action: format(toggleNumberedList) },
				{ name: 'link', icon: LinkVariant, title: t('diary', 'Link'), action: format(insertLink), separated: true },
				{ name: 'preview', icon: Eye, title: t('diary', 'Preview'), action: this.togglePreview, separated: true, alwaysEnabled: true },
				{ name: 'guide', icon: HelpCircleOutline, title: t('diary', 'Markdown guide'), action: this.openGuide, alwaysEnabled: true },
			]
		},
	},
	watch: {
		date: {
			handler() {
				this.flushSave()
				this.preview = false
				this.fetchEntry()
			},
		},
	},
	mounted() {
		// The view is deliberately not part of data(), Vue must not make it reactive
		this.view = new EditorView({
			parent: this.$refs.editorHost,
			state: this.createState(''),
		})
		this.fetchEntry()
	},
	beforeDestroy() {
		this.flushSave()
		this.view.destroy()
	},
	methods: {
		createState(doc) {
			return EditorState.create({
				doc,
				extensions: [
					history(),
					keymap.of([
						{ key: 'Mod-b', run: toggleBold },
						{ key: 'Mod-i', run: toggleItalic },
						...defaultKeymap,
						...historyKeymap,
					]),
					markdown({ base: markdownLanguage }),
					syntaxHighlighting(highlightStyle),
					editorTheme,
					EditorView.lineWrapping,
					placeholder(t('diary', 'Write your entry here')),
					// Let the mobile keyboard help with spelling and capitalization like in any other text field
					EditorView.contentAttributes.of({ spellcheck: 'true', autocorrect: 'on', autocapitalize: 'sentences' }),
					EditorView.updateListener.of((update) => {
						if (update.docChanged) {
							this.onUserChange(update.state.doc.toString())
						}
					}),
				],
			})
		},
		/**
		 * Show the content of a freshly loaded entry. A new state also starts a new
		 * undo history, so undo never brings back the text of another day.
		 *
		 * @param {string} content Markdown of the entry
		 */
		showContent(content) {
			this.content = content
			this.view.setState(this.createState(content))
		},
		onUserChange(content) {
			this.content = content
			this.unSavedChanges = true
			clearTimeout(this.saveTimeout)
			// Remember date and content now, so a pending save always goes to the day it was written for
			const date = this.date
			this.pendingSave = () => this.save(date, content)
			this.saveTimeout = setTimeout(() => this.flushSave(), SAVE_DELAY)
		},
		flushSave() {
			clearTimeout(this.saveTimeout)
			if (this.pendingSave) {
				const save = this.pendingSave
				this.pendingSave = null
				save()
			}
		},
		save(date, content) {
			axios.put(generateUrl('apps/diary/entry/' + date), { content })
				.then(response => {
					if (date === this.date && content === this.content) {
						this.unSavedChanges = false
					}
					this.$emit('entry-edit', date, response.data.isEmpty ? false : response.data.entryContent)
				})
				.catch(error => {
					// TODO Show alert box with error
					// eslint-disable-next-line no-console
					console.log(error)
				})
		},
		insertTemplate(template) {
			const { view } = this
			const lines = template.content.split('\n')
			const firstQuote = lines.findIndex(line => line.startsWith('>'))
			view.dispatch({
				changes: { from: 0, to: view.state.doc.length, insert: template.content },
				userEvent: 'input.template',
			})
			// Put the cursor below the first question, ready to write the answer. This has to wait until the
			// template menu is closed, because closing it moves the focus back to the (now hidden) menu button.
			const cursorLine = view.state.doc.line(Math.min(firstQuote === -1 ? 1 : firstQuote + 2, view.state.doc.lines))
			setTimeout(() => {
				view.focus()
				view.dispatch({ selection: { anchor: cursorLine.from }, scrollIntoView: true })
			}, 100)
		},
		togglePreview() {
			this.preview = !this.preview
			if (!this.preview) {
				this.$nextTick(() => this.view.focus())
			}
		},
		openGuide() {
			window.open(MARKDOWN_GUIDE_URL, '_blank', 'noopener')
		},
		fetchEntry() {
			const date = this.date
			this.status = 'loading'
			axios.get(generateUrl('apps/diary/entry/' + date))
				.then(response => {
					if (date !== this.date) {
						// Another day was selected in the meantime
						return
					}
					this.unSavedChanges = false
					this.showContent(response.data.entryContent || '')
					this.status = 'loaded'
				})
				.catch(error => {
					// eslint-disable-next-line no-console
					console.log(error)
					this.status = 'error'
				})
		},
	},
}
</script>

<style lang="scss">
@import '~github-markdown-css';

#diary-editor {
	position: relative;
	height: 100%;

	#entry-title {
		display: flex;
		align-items: center;
		gap: 16px;
		font-weight: 700;
		font-size: 18px;
		padding-left: 50px;
		padding-top: 16px;
		max-width: 800px;

		.title-loading {
			display: inline-block;
			vertical-align: middle;
		}

		.template-menu {
			font-size: var(--default-font-size);
			font-weight: normal;
		}
	}

	.editor-toolbar {
		display: flex;
		flex-wrap: wrap;
		gap: 2px;
		padding: 8px 0 0 44px;
		max-width: 800px;

		button {
			display: flex;
			align-items: center;
			justify-content: center;
			// Large enough to hit reliably with a finger
			min-width: 40px;
			min-height: 40px;
			margin: 0;
			padding: 0;
			border: none;
			border-radius: var(--border-radius-large, 8px);
			background: transparent;
			color: var(--color-main-text);
			cursor: pointer;

			&.separated {
				margin-left: 12px;
			}

			&:hover:not(:disabled), &:focus-visible, &.active {
				background-color: var(--color-background-hover);
			}

			&:disabled {
				color: var(--color-text-maxcontrast);
				cursor: default;
				opacity: .5;
			}
		}
	}

	.editor-host, .editor-preview {
		max-width: 800px;
		padding-left: 50px;
		padding-right: 16px;
	}

	.editor-preview {
		padding-top: 12px;
		padding-bottom: 50px;
		background-color: var(--color-main-background);
		color: var(--color-main-text);
	}

	@media (max-width: 500px) {
		.editor-toolbar {
			padding-left: 8px;
			gap: 0;

			button {
				min-width: 36px;
			}

			.separated {
				margin-left: 0;
			}
		}

		.editor-host, .editor-preview {
			padding-left: 8px;
			padding-right: 8px;
		}
	}

	#overlay {
		display: flex;
		justify-content: center;
		align-items: center;
		z-index: 99;
		position: absolute;
		top: 0;
		left: 0;
		right: 0;
		bottom: 0;
		background-color: hsl(0, 0%, 0%, 0.5);
	}
}
</style>
