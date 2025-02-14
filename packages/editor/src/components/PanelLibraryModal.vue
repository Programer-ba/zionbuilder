<template>
	<Modal
		:show="true"
		append-to=".znpb-center-area"
		:width="1440"
		class="znpb-library-modal"
		v-model:fullscreen="fullSize"
		:close-on-escape="true"
		@close-modal="closePanel('PanelLibraryModal')"
	>
		<template v-slot:header>
			<div class="znpb-library-modal-header">
				<span
					v-if="previewOpen || multiple || importActive"
					@click.stop="closeBody"
					class="znpb-library-modal-header-preview__back"
				>
					<Icon
						icon="long-arrow-right"
						rotate="180"
					/>
					{{$translate('go_back')}}
				</span>
				<div
					v-if="previewOpen || importActive"
					class="znpb-library-modal-header-preview"
				>

					<h2
						class="znpb-library-modal-header-preview__title"
						v-html="computedTitle"
					>
					</h2>
				</div>
				<template v-else>
					<h2
						class="znpb-library-modal-header__title"
						:class="{'znpb-library-modal-header__title--active': localActive}"
						@click="localActive=true, zionActive=false"
					>
						{{$translate('local_library')}}
					</h2>
					<h2
						class="znpb-library-modal-header__title"
						:class="{'znpb-library-modal-header__title--active': zionActive}"
						@click="localActive=false, zionActive=true"
					>
						{{$translate('zion_library')}}
					</h2>
				</template>
				<div class="znpb-library-modal-header__actions">
					<Tooltip
						v-if="previewOpen"
						append-to="element"
						tag="span"
						:content="$translate('library_insert_tooltip')"
						:modifiers="[{name: 'offset',options: {	offset: [0, 10]}}]"
						placement="top"
						strategy="fixed"
					>

						<a
							v-if="!isProActive && activeItem.pro"
							class="znpb-button znpb-button--line znpb-button-buy-pro"
							:href="purchaseURL"
							target="_blank"
						>{{$translate('buy_pro')}}
						</a>

						<a
							v-else-if="isProActive && !isProConnected && activeItem.pro"
							class="znpb-button znpb-button--line"
							target="_blank"
							:href="dashboardURL"
						>{{$translate('activate_pro')}}
						</a>

						<Button
							v-else
							type="secondary"
							@click.stop="insertLibraryItem"
							class="znpb-library-modal-header__insert-button"
						>
							<span v-if="!insertItemLoading">
								{{$translate('library_insert')}}
							</span>
							<Loader
								v-else
								:size="13"
							/>
						</Button>
					</Tooltip>

					<template v-else>
						<Button
							v-if="localActive"
							type="secondary"
							@click="importActive = !importActive , templateUploaded=!templateUploaded "
						>

							<Icon icon="import" />
							{{$translate('import')}}
						</Button>

						<Tooltip
							v-if="!importActive"
							:content="$translate('refresh_tooltip')"
							tag="span"
							placement="top"
							class="znpb-modal__header-button znpb-modal__header-button--library-refresh znpb-button znpb-button--line"
						>
							<Icon
								icon="refresh"
								@click="onRefresh"
								:size="14"
								:class="{['loading']: libLoading}"
							/>
						</Tooltip>

					</template>

					<Icon
						:icon="fullSize ? 'shrink' : 'maximize'"
						class="znpb-modal__header-button"
						:size="14"
						@click.stop="fullSize=!fullSize"
					/>

					<Icon
						icon="close"
						:size="14"
						@click="togglePanel('PanelLibraryModal')"
						class="znpb-modal__header-button"
					/>
				</div>
			</div>
		</template>
		<LibraryUploader
			v-if="importActive"
			@file-uploaded="onTemplateUpload"
		/>

		<LibraryPanel
			v-if="!localActive && !importActive"
			@activate-preview="previewOpen=true, activeItem=$event"
			@activate-multiple="multiple=$event"
			@loading-start="libLoading = true"
			@loading-end="libLoading = false"
			:preview-open="previewOpen"
			:multiple="multiple"
			:import-active="importActive"
			ref="libraryContent"
		/>

		<localLibrary
			ref="localLibraryContent"
			v-if="localActive && !importActive"
			:preview-open="previewOpen"
			@activate-preview="activatePreview"
		/>
	</Modal>

</template>

<script>
import { ref, watch } from 'vue'
import { addOverflow, removeOverflow } from '../utils/overflow'
import { regenerateUIDsForContent } from '@utils'
import { insertTemplate } from '@zb/rest'
import { usePanels, useElements, useEditorData } from '@composables'
import { useLibrary, useLocalLibrary } from '@zionbuilder/composables'

// Components
import LibraryPanel from './LibraryPanel.vue'
import LibraryUploader from './library-panel/LibraryUploader.vue'
import localLibrary from './library-panel/localLibrary.vue'

export default {
	name: 'LibraryModal',
	components: {
		LibraryPanel,
		LibraryUploader,
		localLibrary,
	},
	provide () {
		return {
			Library: this
		}
	},
	setup (props) {
		const { togglePanel, closePanel } = usePanels()
		const { fetchTemplates, loading } = useLocalLibrary()
		let libLoading = ref(false)

		const { editorData } = useEditorData()
		const isProActive = ref(editorData.value.plugin_info.is_pro_active)
		const isProConnected = ref(editorData.value.plugin_info.is_pro_connected)
		const purchaseURL = ref(editorData.value.urls.purchase_url)
		watch(loading, (newVal) => {
			libLoading.value = newVal
		})

		return {
			closePanel,
			togglePanel,
			fetchTemplates,
			libLoading,
			editorData,
			isProActive,
			isProConnected,
			purchaseURL
		}
	},
	data () {
		return {
			importActive: false,
			multiple: false,
			fullSize: false,
			localActive: false,
			zionActive: true,
			previewOpen: false,
			activeItem: null,
			insertItemLoading: false,
			templateUploaded: false
		}
	},

	computed: {
		computedTitle () {
			return this.previewOpen ? this.activeItem.post_title : this.$translate('import')
		}

	},
	mounted () {
		addOverflow(document.getElementById('znpb-editor-iframe').contentWindow.document.body)
	},
	methods: {
		onTemplateUpload () {
			this.importActive = false
			this.localActive = true
			this.templateUploaded = true
		},
		insertLibraryItem () {
			this.insertItemLoading = true
			this.insertItem(this.activeItem).then(() => {
				this.insertItemLoading = false
			})
		},

		onRefresh () {
			this.templateUploaded = false

			this.localActive ? this.fetchTemplates(true) : this.$refs.libraryContent.getDataFromServer(false)
		},
		closeBody () {
			if (this.multiple && this.previewOpen) {
				this.previewOpen = false
			} else {
				this.previewOpen = false
				this.multiple = false
				this.importActive = false
			}
		},
		activatePreview (item) {
			this.activeItem = item
			this.previewOpen = true
		},

		/**
		 * Insert item
		 *
		 * Handles template insertion
		 * Will generate new UIDs for elements
		 * Will add the page custom css and js
		 * Will add the custom css classes used for element
		 */
		insertItem (item) {
			return new Promise((resolve, reject) => {
				insertTemplate(item).then((response) => {
					const { template_data: templateData } = response.data
					const { insertElement, activeElement } = useLibrary()

					const { togglePanel } = usePanels()

					// Check to see if this is a single element or a group of elements
					let compiledTemplateData = templateData.element_type ? [templateData] : templateData
					const newElement = regenerateUIDsForContent(compiledTemplateData)

					if (activeElement.value) {
						insertElement(newElement)
					} else {
						const { getElement } = useElements()
						const element = getElement(this.editorData.page_id)
						element.addChildren(newElement)
					}

					togglePanel('PanelLibraryModal')

					resolve(true)
				}).catch((error) => {
					reject(error)
				})
			})
		}
	},
	beforeUnmount () {
		const { unsetActiveElementForLibrary } = useLibrary()
		removeOverflow(document.getElementById('znpb-editor-iframe').contentWindow.document.body)
		unsetActiveElementForLibrary()
	}
}
</script>
<style lang="scss">
.znpb-library-modal {
	& > .znpb-modal__wrapper {
		width: 100%;
		height: 860px;

		@media (max-width: 1440px) {
			width: calc(100% - 40px);
		}
	}
}

.znpb-modal__header-button--library-refresh {
	display: flex;
	justify-content: center;
	padding: 0;

	.znpb-editor-icon-wrapper {
		padding: 11px;

		&.loading {
			animation: rotation 0.55s infinite linear;
		}
	}
}

@keyframes rotation {
	from {
		transform: rotate(0deg);
	}
	to {
		transform: rotate(359deg);
	}
}

.znpb-library-modal-header {
	position: relative;
	display: flex;
	justify-content: center;
	flex-shrink: 0;
	height: 58px;
	color: var(--zb-surface-lighter-color);
	border-bottom: 1px solid var(--zb-surface-border-color);

	&__title {
		display: flex;
		align-items: center;
		padding: 10px 30px;
		color: var(--zb-surface-text-color);
		font-size: 13px;
		font-weight: 500;
		line-height: 1;
		border-right: 1px solid var(--zb-surface-color);
		border-left: 1px solid var(--zb-surface-color);
		cursor: pointer;

		&--active {
			color: var(--zb-surface-text-active-color);
			box-shadow: 0 1px 0 0 var(--zb-surface-color);
			border-right: 1px solid var(--zb-surface-border-color);
			border-left: 1px solid var(--zb-surface-border-color);
		}
	}

	& > .znpb-library-modal-header__actions {
		position: absolute;
		right: 0;
		display: flex;
		align-items: center;
		align-self: center;

		.znpb-button--secondary,
		.znpb-button-buy-pro {
			display: flex;
			align-items: center;
			padding: 13px 20px;
			margin-right: 15px;

			.znpb-editor-icon-wrapper {
				margin-right: 5px;
			}
		}
	}

	&-preview {
		&__back {
			position: absolute;
			top: 23px;
			left: 20px;
			display: flex;
			align-self: center;
			color: var(--zb-surface-text-color);
			font-weight: 500;
			transition: color 0.15s;
			cursor: pointer;

			&:hover {
				color: var(--zb-surface-text-hover-color);
			}

			span:first-child {
				margin-right: 12px;
			}
		}

		&__title {
			padding: 21px 20px;
			color: var(--zb-surface-text-active-color);
			font-size: 16px;
			font-weight: 500;
		}
	}
}

.znpb-editor-library-modal-loader {
	height: 100%;
}
</style>
