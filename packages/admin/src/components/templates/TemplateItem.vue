<template>
	<div>
		<div
			class="znpb-admin-single-template"
			:class="{'znpb-admin-single-template--active': isActive}"
		>

			<span class="znpb-admin-single-template__title">{{template.post_title}}</span>
			<span class="znpb-admin-single-template__author">{{template.author_name}}</span>
			<div class="znpb-admin-single-template__shortcode">

				<Tooltip
					:hide-after="isCopied ? 500 : null"
					@hide="isCopied = false"
				>
					<template v-slot:content>
						<div>
							<span>{{ isCopied ? copiedText : copyText }}</span>
						</div>
					</template>

					<BaseInput
						ref="templateInput"
						:modelValue="template.shortcode"
						readonly
						spellcheck="false"
						autocomplete="false"
						class="znpb-admin-single-template__input"
						@click="copyTextInput(template.shortcode)"
					>
						<template v-slot:suffix>
							<Icon
								icon="copy"
								@click="copyTextInput(template.shortcode)"
							/>
						</template>
					</BaseInput>
				</Tooltip>
			</div>
			<div class="znpb-admin-single-template__actions">
				<template v-if="!localLoading">
					<Tooltip
						:content="$translate('library_insert_tooltip')"
						append-to="element"
						class="znpb-admin-single-template__action znpb-insert-icon-pop"
						tag="div"
						:modifiers="[
							{
								name: 'offset',
								options: {
									offset: [0, 15],
								},
							},
						]"
						placement="top"
						strategy="fixed"
						v-if="showInsert"
					>
						<span
							class="znpb-button znpb-button--secondary"
							@click="$emit('insert', template)"
						>
							{{$translate('library_insert')}}
						</span>
					</Tooltip>

					<Tooltip
						:content="$translate('edit_template')"
						append-to="element"
						:modifiers="[
							{
								name: 'offset',
								options: {
									offset: [0, 15],
								},
							},
						]"
						placement="top"
						strategy="fixed"
						class="znpb-admin-single-template__action znpb-edit-icon-pop"
					>

						<Icon
							icon="edit"
							@click="editUrl"
						/>

					</Tooltip>

					<Tooltip
						:content="$translate('delete_template')"
						append-to="element"
						class="znpb-admin-single-template__action znpb-delete-icon-pop"
						:modifiers="[
							{
							name: 'offset',
							options: {
								offset: [0, 15],
							},
							},
						]"
						placement="top"
						strategy="fixed"
					>
						<Icon
							icon="delete"
							@click="$emit('delete-template', template)"
						/>
					</Tooltip>

					<Tooltip
						:content="$translate('export_template')"
						append-to="element"
						class="znpb-admin-single-template__action znpb-export-icon-pop"
						placement="top"
						:modifiers="[
								{
								name: 'offset',
								options: {
									offset: [0, 15],
								},
								},
							]"
						strategy="fixed"
					>
						<Icon
							icon="export"
							@click="exportLocalTemplate"
						/>
					</Tooltip>

					<Tooltip
						:content="$translate('preview_template')"
						class="znpb-admin-single-template__action znpb-preview-icon-pop"
						append-to="element"
						placement="top"
						:modifiers="[
							{
							name: 'offset',
							options: {
								offset: [0, 15],
							},
							},
						]"
						strategy="fixed"
					>
						<Icon
							icon="eye"
							@click="$emit('show-modal-preview', true)"
						/>
					</Tooltip>
				</template>
				<Loader v-else />
			</div>

		</div>
		<p
			class="znpb-admin-single-template--error"
			v-if="errorMessage.length > 0"
		>{{errorMessage}}</p>
	</div>
</template>

<script>
import { ref } from 'vue'
import { exportTemplateById } from '@zionbuilder/rest'
import { saveAs } from 'file-saver'

export default {
	name: 'TemplateItem',
	props: {
		template: {
			type: Object,
			required: false
		},
		showInsert: {
			type: Boolean,
			required: false
		},
		loading: {
			type: Boolean,
			required: false
		},
		error: {
			type: String,
			required: false
		},
		active: {
			type: Boolean,
			required: false
		}
	},

	setup (props) {
		const isActive = ref(props.active)

		if (isActive.value) {
			setTimeout(() => {
				isActive.value = false
			}, 1000);
		}

		return {
			isActive
		}
	},
	data () {
		return {
			isCopied: false,
			copiedText: 'Copied',
			copyText: 'Copy',
			localLoading: this.loading,
			errorMessage: '',
		}
	},

	watch: {
		loading (newVal) {
			this.localLoading = newVal
		},
		error (newVal) {
			this.errorMessage = newVal
		}
	},

	methods: {

		editUrl () {
			window.open(this.template.edit_url)
		},

		copyTextInput (text) {
			this.isCopied = true
			// Create new element
			var el = document.createElement('textarea')
			// Set value (string to be copied)
			el.value = text
			// Set non-editable to avoid focus and move outside of view
			el.setAttribute('readonly', '')
			el.style = {
				position: 'absolute',
				left: '-9999px'
			}
			document.body.appendChild(el)
			// Select text inside element
			el.select()
			// Copy text to clipboard
			document.execCommand('copy')
			// Remove temporary element
			document.body.removeChild(el)
		},
		exportLocalTemplate () {
			this.localLoading = true
			this.errorMessage = ''
			exportTemplateById(this.template.ID).then((response) => {
				var blob = new Blob([response.data], { type: 'application/zip' })
				saveAs(blob, `${this.template.post_title}.zip`)
			}).catch((error) => {
				this.errorMessage = error.response.data.message
			})
				.finally(() => {
					this.localLoading = false
				})
		}
	}
}
</script>
<style lang="scss">
.znpb-admin-single-template--active {
	box-shadow: 0 0 4px #006dd2;
}
.znpb-admin-single-template {
	@extend %list-item-helper;
	padding: 9px 0;

	@media (max-width: 767px) {
		flex-direction: column;
		align-items: flex-start;
		padding: 18px 0;
	}

	.zion-input {
		background: var(--zb-input-bg-color);
	}

	&__actions,
	&__shortcode,
	&__author,
	&__title {
		flex: 1;
		padding: 0 14px;
	}

	&__title {
		flex-grow: 2;
		padding: 0 20px;
		color: var(--zb-surface-text-active-color);
		font-size: 13px;
		font-weight: 500;
	}
	&__author {
		padding: 0 14px;
		color: var(--zb-surface-text-color);
		font-size: 13px;
		font-weight: 500;
	}
	&__shortcode {
		position: relative;
		flex-basis: 160px;
		padding: 0 14px;

		@media (max-width: 767px) {
			flex-basis: auto;
			width: 100%;
		}

		.zion-input input {
			padding: 10.5px 12px;
		}

		.zion-input__suffix {
			padding-right: 14px;
			cursor: pointer;
			.znpb-editor-icon-wrapper {
				opacity: 0;
				visibility: hidden;
			}
		}
		&:hover {
			.znpb-editor-icon-wrapper {
				opacity: 1;
				visibility: visible;
			}
		}
	}

	&__actions {
		position: relative;
		display: flex;
		justify-content: flex-end;
		align-items: center;
		padding: 0 20px;
		font-size: 14px;
		text-align: right;

		.znpb-admin-small-loader {
			top: -12px;
		}
	}

	&__action {
		&.znpb-insert-icon-pop,
		&.znpb-edit-icon-pop,
		&.znpb-delete-icon-pop,
		&.znpb-export-icon-pop {
			margin-right: 10px;
		}

		&.znpb-delete-icon-pop {
			&:last-of-type {
				margin-right: 0;
			}
		}

		.znpb-editor-icon-wrapper {
			display: block;
		}

		.znpb-editor-icon-wrapper,
		a {
			color: var(--zb-surface-text-color);
			transition: color 0.15s;
			cursor: pointer;

			&:hover {
				color: var(--zb-surface-text-hover-color);
			}
		}
	}

	@media (max-width: 767px) {
		&__title,
		&__author,
		&__shortcode {
			margin-bottom: 10px;
		}

		&__title {
			padding: 0 14px;
		}
	}

	&--error {
		color: var(--zb-error-color);
	}
}
</style>
