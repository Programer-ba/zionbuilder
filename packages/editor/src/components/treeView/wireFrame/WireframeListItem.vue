<template>
	<li
		class="znpb-wireframe-item"
		:class="getClasses"
		@click.stop="element.focus"
		@contextmenu.stop.prevent="showElementMenu"
	>
		<div class="znpb-wireframe-item__header">
			<div class="znpb-wireframe-item__header-area znpb-wireframe-item__header-area--left">
				<Icon
					class="znpb-wireframe-item__header-item znpb-wireframe-item__header-button znpb-wireframe-item__header-more znpb-utility__cursor--pointer"
					v-if="element.isWrapper"
					icon="select"
					:rotate="expanded ? '180' : false"
					@click="expanded = !expanded"
				/>
			</div>
			<div class="znpb-wireframe-item__header-area znpb-wireframe-item__header-area--center">
				<img
					v-if="get_element_image"
					:src="get_element_image"
					class="znpb-wireframe-itemImage"
				/>

				<Icon
					v-else
					:icon="get_element_icon"
					:size="24"
					class="znpb-wireframe-itemIcon"
				/>
				<InlineEdit
					class="znpb-wireframe-item__header-title znpb-wireframe-item__header-item"
					v-model="element.name"
					v-model:active="element.activeElementRename"
				/>
			</div>
			<div class="znpb-wireframe-item__header-area znpb-wireframe-item__header-area--right">

				<Tooltip
					v-if="!element.isVisible"
					:content="$translate('enable_hidden_element')"
					class="znpb-tree-view__item-enable-visible znpb-wireframe-item__header-area--visibility-icon"
				>
					<transition name="fade">
						<Icon
							icon="visibility-hidden"
							@click="element.toggleVisibility()"
							class="znpb-editor-icon-wrapper--show-element"
						>
						</Icon>
					</transition>

				</Tooltip>

				<div
					class="znpb-element-options__container"
					@click.stop="showElementMenu"
					ref="elementOptionsRef"
				>
					<Icon
						class="znpb-element-options__dropdown-icon znpb-utility__cursor--pointer"
						icon="more"
					/>
				</div>
				<Icon
					icon="delete"
					@click.stop="element.delete()"
					class="znpb-wireframe-item__delete-icon"
				/>

			</div>
		</div>

		<WireframeList
			v-if="expanded && element.isWrapper"
			:element="element"
			class="znpb-wireframe-item__content"
			:class="{[`znpb-flex--${hasFlexDirection}`]: hasFlexDirection}"
		/>

	</li>
</template>
<script>
import { computed } from 'vue'
import SortablePlaceholder from '../../../common/SortablePlaceholder.vue'
import SortableHelper from '../../../common/SortableHelper.vue'
import { getOptionValue } from '@zb/utils'
import { on } from '@zb/hooks'
import { useTreeViewItem } from '../useTreeViewItem'
import { useElementTypes } from "@composables";
export default {
	name: 'element-wireframe-view',
	components: {
		SortablePlaceholder,
		SortableHelper
	},
	props: {
		element: {
			type: Object,
			required: true
		}
	},
	setup (props) {
		const {
			showElementMenu,
			elementOptionsRef,
			isActiveItem
		} = useTreeViewItem(props)
		const columnSize = computed(() => props.element.options.column_size)

		const { getElementIcon, getElementImage } = useElementTypes();

		const get_element_image = getElementImage(props.element.element_type);
		const get_element_icon = getElementIcon(props.element.element_type);

		return {
			showElementMenu,
			elementOptionsRef,
			isActiveItem,
			columnSize,
			get_element_image,
			get_element_icon
		}
	},
	data () {
		return {
			expanded: true,
			isNameChangeActive: false,
			hovered: false,
			showColumnTemplates: false
		}
	},
	computed: {
		hasFlexDirection () {
			let orientation = 'column'
			let mediaOrientation = getOptionValue(this.element.options, '_styles.wrapper.styles.default.default.flex-direction')

			if (this.element.element_type === 'zion_section') {
				mediaOrientation = getOptionValue(this.element.options, '_styles.inner_content_styles.styles.default.default.flex-direction', 'row')
			}

			if (mediaOrientation) {
				orientation = mediaOrientation
			}

			return orientation
		},
		getClasses () {
			let cssClass = {
				[`znpb-wireframe-item--item--hidden`]: !this.element.isVisible,
				[`znpb-wireframe-item--${this.element.element_type}`]: this.element.element_type,
				[`znpb-wireframe-item__empty`]: !this.element.content.length
			}

			if (this.columnSize) {
				Object.keys(this.columnSize).forEach((key) => {
					let responsivePrefix = this.getColumnResponsivePrefix(key)
					cssClass[`zb-column--${responsivePrefix}${this.columnSize[key]}`] = !!this.columnSize[key]
				})
			}
			return cssClass
		}

	},
	methods: {
		activateRenameElement () {
			if (this.isActiveItem) {
				this.isNameChangeActive = true
			}
		},
		getColumnResponsivePrefix (responsiveMediaId) {
			const devices = {
				default: '',
				laptop: 'lg--',
				tablet: 'md--',
				mobile: 'sm--'
			}

			return devices[responsiveMediaId]
		},
		shrinkPanel () {
			this.expanded = false
		}
	}
}
</script>
<style lang="scss">
@import "~@zionbuilder/css-variables/frontend/_grid.scss";

.znpb-editor-icon-wrapper--show-element {
	padding: 15px 15px 15px;
	transition: opacity 0.2s ease;
	cursor: pointer;

	&:hover {
		opacity: 1;
	}
}

.znpb-wireframe-view-wrapper {
	padding-top: 5px;
	padding-bottom: 50px;
}
.znpb-wireframe-item {
	flex-grow: 1;
	flex-shrink: 1;
	padding: 0 15px 30px 15px;

	&Image {
		height: 24px;
	}
	&Image,
	&Icon {
		padding-right: 15px;
	}

	.znpb-wireframe-item--column .znpb-empty-placeholder {
		border-right: 2px solid #faeec6;
		border-left: 2px solid #faeec6;
	}

	.znpb-empty-placeholder {
		height: auto;
		min-height: auto;
		padding-top: 15px;
	}
	&__empty {
		display: block;
		.znpb-wireframe-item__content {
			padding: 15px;
		}
	}
	&__sortable {
		cursor: pointer;
	}

	& > .znpb-wireframe-item__header {
		background: var(--zb-element-color);
	}

	&__delete-icon {
		padding: 13px 20px 13px 0;
		transition: opacity 0.2s;
		cursor: pointer;

		span {
			transition: none;
		}

		&:hover,
		&:focus {
			opacity: 0.5;
		}
	}

	.znpb-element-toolbox__add-element-button {
		position: absolute;
		top: 100%;
		left: 50%;
		margin: 0 auto;
		text-align: center;
		transform: translate(-50%, -50%);

		.znpb-editor-icon-wrapper {
			width: 34px;
			height: 34px;
			color: #fff;
			background: var(--zb-element-color);
		}
	}

	&__header {
		display: flex;
		width: 100%;
		color: var(--zb-primary-text-color);
		text-align: center;
		transition: all 0.2s;

		&-area {
			display: flex;
			flex-basis: 0;
			flex-grow: 1;

			&--visibility-icon {
				display: flex;
				align-items: center;

				.znpb-editor-icon-wrapper--show-element {
					padding-right: 0;
				}
			}
			&--center {
				justify-content: center;
				align-items: center;
				overflow: hidden;

				.znpb-utility__text--elipse {
					&:after {
						display: none;
					}
				}
				& > span {
					color: #fff;
				}
			}
			&--right {
				position: relative;
				justify-content: flex-end;
				min-width: 110px;
			}
		}

		&-item {
			position: relative;
			padding: 13px 20px;
			transition: opacity 0.2s;

			&:hover {
				opacity: 0.5;
			}
			&:focus {
				outline: 0;
			}
		}

		&-title {
			overflow: hidden;
			padding: 0;
			color: #fff;
			font-weight: 500;
			text-overflow: ellipsis;
			white-space: nowrap;
			cursor: text;

			.znpb-utility__text--elipse {
				width: 100%;
				max-width: 170px;
			}
		}
	}

	&--zion_section {
		position: relative;
		display: flex;
		flex-wrap: wrap;
		justify-content: center;
		align-items: center;
		flex: 1 1 auto;
		width: auto;

		& > .znpb-wireframe-item__header {
			background: var(--zb-section-color);
		}

		& > .znpb-wireframe-item__content {
			position: relative;
			display: flex;
			flex-wrap: wrap;
			flex: 1 1 auto;
			width: 100%;
		}
	}

	&--zion_column {
		position: relative;
		display: flex;
		flex-direction: column;
		flex-wrap: wrap;
		flex-grow: 1;
		min-height: 1px;

		& > .znpb-wireframe-item__header {
			background: var(--zb-column-color);
		}
		& > .znpb-wireframe-item__content {
			position: relative;
			display: flex;
			flex-direction: column;
			flex-wrap: wrap;
			flex: 1 1 auto;
			width: 100%;
			border: 2px solid #faeec6;
		}
		&
			> .znpb-wireframe-item__content
			> .znpb-element-toolbox__add-element-button {
			& > .znpb-editor-icon-wrapper {
				background: var(--zb-column-color);
			}
		}
	}

	&--item--hidden {
		opacity: 0.5;
	}
	&__content {
		position: relative;
		width: 100%;
		&.znpb-flex {
			&--row {
				flex-direction: row;
			}
			&--column {
				flex-direction: column;
				justify-content: center;
				align-items: stretch;
			}
		}
	}

	// Children of content should have dashed border instead of shadow
	&__content &__content {
		box-shadow: none;
		border-top: none;
	}

	// Children of content should have dashed border instead of shadow
	&__content &__content {
		box-shadow: none;
		border-top: none;
	}

	//nested children
	ul.znpb-wireframe-item__content {
		padding: 30px 15px;
		background: var(--zb-surface-light-color);
		cursor: pointer;
	}
}
</style>
