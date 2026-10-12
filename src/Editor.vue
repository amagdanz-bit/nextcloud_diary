<template>
	<div id="diary-editor">
		<div id="entry-title">
			<span>
				<i v-if="isLoading" class="fa fa-spinner fa-spin" />
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
		<VueSimplemde ref="markdownEditor"
			:model-value="content"
			:configs="configs"
			preview-class="markdown-body" />
		<div v-if="isLoading" id="overlay">
			<i class="fa fa-spinner fa-spin fa-10x" />
		</div>
	</div>
</template>
<script>
import VueSimplemde from 'vue-simplemde'
import { NcActions, NcActionButton } from '@nextcloud/vue'
import TextBoxEditOutline from 'vue-material-design-icons/TextBoxEditOutline'

import axios from '@nextcloud/axios'
import { generateUrl } from '@nextcloud/router'
import moment from '@nextcloud/moment'
import templates from './templates'

export default {
	name: 'Editor',
	components: { VueSimplemde, NcActions, NcActionButton, TextBoxEditOutline },
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
			editor: null,
			content: '',
			templates,
			configs: {
				toolbar: ['bold', 'italic', 'strikethrough', 'heading', '|', 'quote', 'unordered-list', 'ordered-list', '|', 'link', '|', 'preview', '|', 'guide'],
				autoDownloadFontAwesome: false,
				placeholder: t('diary', 'Write your entry here'),
				spellChecker: false,
				status: false,
			},
		}
	},
	computed: {
		simplemde() {
			return this.$refs.markdownEditor.simplemde
		},
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
				&& (this.status === 'loaded' || this.status === 'writing')
				&& this.content.trim() === ''
		},
	},
	created() {
		this.$watch(() => this.$route.params, () => this.fetchEntry(), { immediate: true })
	},
	mounted() {
		this.simplemde.codemirror.on('change', () => {
			// A load is a change, so we need to catch this.
			// We compare the content because switching to a page will trigger the change event TWICE! (Or not at all, if
			// we don't set the value of the editor to the content). So whenever these values are equal, we did not reach
			// the other workaround where we manually set the content to the value of the editor.
			if (this.status === 'loaded' || this.content === this.simplemde.value()) {
				this.status = 'writing'
				return
			}
			// This line here is SUPER important! Because although simplemde has the model set to this.content, changing
			// the content in the editor WON'T change the content of the content property. How long did it take me to find
			// this out? Like two days.
			this.content = this.simplemde.value()
			this.unSavedChanges = true
			clearTimeout(this.timeout)
			const saveFunction = () => {
				const newContent = this.simplemde.value()
				const payload = {
					content: newContent,
				}
				// Send content to backend
				axios.put(generateUrl('apps/diary/entry/' + this.date), payload)
					.then(response => {
						this.unSavedChanges = false
						this.$emit('entry-edit', this.date, response.data.isEmpty ? false : response.data.entryContent)
					})
					.catch(error => {
						// TODO Show alert box with error
						// eslint-disable-next-line no-console
						console.log(error)
					})
			}
			this.timeout = setTimeout(saveFunction, 500)
		})
	},
	methods: {
		insertTemplate(template) {
			// Make sure the change handler treats this as an edit (and saves it), not as loading an entry
			this.status = 'writing'
			this.simplemde.value(template.content)
			// Put the cursor below the first question, ready to write the answer. This has to wait until the
			// template menu is closed, because closing it moves the focus back to the (now hidden) menu button.
			const codemirror = this.simplemde.codemirror
			const firstQuote = codemirror.getValue().split('\n').findIndex(line => line.startsWith('>'))
			setTimeout(() => {
				codemirror.focus()
				codemirror.setCursor({ line: firstQuote === -1 ? 0 : firstQuote + 1, ch: 0 })
			}, 100)
		},
		fetchEntry() {
			this.status = 'loading'
			axios.get(generateUrl('apps/diary/entry/' + this.$route.params.date))
				.then(response => {
					if (response.data.entryContent) {
						this.content = response.data.entryContent
					} else {
						this.content = ''
					}
					this.status = 'loaded'
					// This ensures, that the right content is immediately shown. Actually, the model should force the editor to
					// update, but that doesn't seem to happen when moving between dates
					this.simplemde.value(this.content)
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
@import '~@fortawesome/fontawesome-free/css/all.min.css';
@import '~simplemde/dist/simplemde.min.css';
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

		.template-menu {
			font-size: var(--default-font-size);
			font-weight: normal;
		}
	}

	.vue-simplemde {
		padding-left: 32px;
		max-width: 800px;
		@media (max-width: 500px) {
			padding-left: 0;
		}

		.CodeMirror {
			background-color: var(--color-main-background);
			color: var(--color-main-text);
			border: none;
		}

		.CodeMirror, .CodeMirror-scroll {
			padding-bottom: 50px;
		}

		.CodeMirror-cursor {
			border-color: var(--color-main-text);
		}

		.CodeMirror-code {
			width: unset !important;
			border: none !important;
		}

		.editor-toolbar {
			border: none;

			a {
				color: var(--color-main-text) !important;

				&.active, &:hover {
					background-color: var(--color-background-hover) !important;
				}
			}

			&.disabled-for-preview {
				a:not(.no-disable) {
					background-color: var(--color-background-darker) !important;
					color: var(--color-text-lighter) !important;
				}
			}
		}

		.editor-preview {
			background-color: var(--color-main-background);
			color: var(--color-main-text);
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
