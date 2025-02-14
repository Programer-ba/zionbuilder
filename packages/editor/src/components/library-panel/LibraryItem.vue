<template>

	<li
		class="znpb-editor-library-modal__item"
		:class="{ 'znpb-editor-library-modal__item--favorite' : favorite }"
	>
		<div class="znpb-editor-library-modal__itemInner">
			<div
				class="znpb-editor-library-modal__item-image"
				:class="{['--no-image']: !item.thumbnail}"
			>
				<img
					:src="item.thumbnail"
					v-cloak
					@click="$emit('activate-item',item)"
					v-if="item.thumbnail"
				/>
			</div>

			<div
				v-if="item.pro"
				class="znpb-editor-library-modal__item-pro"
			>{{$translate('pro')}}
			</div>

			<div class="znpb-editor-library-modal__item-bottom">
				<h4 class="znpb-editor-library-modal__item-title">{{item.name}}</h4>
				<div
					class="znpb-editor-library-modal__item-actions"
					v-if="!insertItemLoading"
				>
					<a
						v-if="!isProActive && item.pro"
						class="znpb-button znpb-button--line"
						:href="purchaseURL"
						target="_blank"
					>{{$translate('buy_pro')}}
					</a>

					<a
						v-else-if="isProActive && !isProConnected && item.pro"
						class="znpb-button znpb-button--line"
						target="_blank"
						:href="dashboardURL"
					>{{$translate('activate_pro')}}
					</a>

					<Tooltip
						v-else
						tag="span"
						:content="$translate('library_insert_tooltip')"
						append-to="element"
						class="znpb-editor-library-modal__item-action"
						placement="top"
						:modifiers="[
						{
						name: 'offset',
						options: {
							offset: [0, 3],
						},
						},
					]"
						strategy="fixed"
					>
						<span
							@click.stop="insertLibraryItem"
							class="znpb-button znpb-button--line"
						>{{$translate('library_insert')}}</span>
					</Tooltip>

					<Tooltip
						tag="span"
						:content="$translate('library_click_preview_tooltip')"
						append-to="element"
						class="znpb-editor-library-modal__item-action"
						placement="top"
						:modifiers="[
						{
						name: 'offset',
						options: {
							offset: [0, 3],
						},
						},
					]"
						strategy="fixed"
					>
						<Icon
							icon="eye"
							@click="$emit('activate-item', item)"
						/>
					</Tooltip>
				</div>
				<Loader
					v-else
					:size="12"
				/>

			</div>
			<div
				v-if="item.type === 'multiple'"
				class="znpb-editor-library-modal__item-bottom-multiple"
			>

			</div>
		</div>
	</li>

</template>
<script>
import { ref, inject } from 'vue'
import { useEditorData } from '@composables'

export default {
	name: 'LibraryItem',
	props: {
		item: {
			type: Object,
			required: false
		},
		favorite: {
			type: Boolean,
			required: false
		}
	},
	data () {
		return {
			insertItemLoading: false
		}
	},
	setup (props, { emit }) {
		const Library = inject('Library')
		const { editorData } = useEditorData()
		const isProActive = ref(editorData.value.plugin_info.is_pro_active)
		const isProConnected = ref(editorData.value.plugin_info.is_pro_connected)
		const dashboardURL = ref('')
		const purchaseURL = ref('')

		dashboardURL.value = `${editorData.value.urls.zion_admin}#/pro-license`
		purchaseURL.value = editorData.value.urls.purchase_url

		return {
			purchaseURL,
			dashboardURL,
			isProConnected,
			isProActive,
			Library
		}
	},
	methods: {
		insertLibraryItem () {
			this.insertItemLoading = true
			// If it's pro, get the download URL

			this.Library.insertItem(this.item).then(() => {

			}).finally(() => {
				this.insertItemLoading = false
			})
		}
	}
}
</script>
<style lang="scss">
.znpb-editor-library-modal__item--grid-sizer,
.znpb-editor-library-modal__item {
	width: 100%;
}

@media (min-width: 992px) {
	.znpb-editor-library-modal__item--grid-sizer,
	.znpb-editor-library-modal__item {
		width: 50%;
	}

	.znpb-editor-library-modal__item--gutter-sizer {
		width: 20px;
	}
}

@media (min-width: 1200px) {
	.znpb-editor-library-modal__item--grid-sizer,
	.znpb-editor-library-modal__item {
		width: calc(100% / 3);
	}
}

.znpb-editor-library-modal__item {
	position: relative;
	float: left;
	padding: 10px;
	margin-bottom: 20px;
	border-radius: 3px;
	transition: box-shadow 0.2s;
	cursor: pointer;

	&Inner:hover {
		box-shadow: 0 12px 30px 0 var(--zb-surface-shadow-hover);

		.znpb-editor-library-modal__item-bottom-multiple {
			box-shadow: 0 12px 30px 0 var(--zb-surface-shadow-hover);
		}
	}

	&-image {
		border-bottom: 1px solid var(--zb-surface-lighter-color);

		&.--no-image {
			min-height: 180px;
			background-color: var(--zb-surface-lighter-color);
		}

		img {
			display: block;
		}
	}

	&-pro {
		position: absolute;
		top: 20px;
		right: 20px;
		padding: 5px 8px;
		color: #fff;
		font-size: 10px;
		font-weight: 700;
		text-transform: uppercase;
		background-color: var(--zb-pro-color);
		border-radius: 2px;
	}

	&-bottom {
		position: relative;
		display: flex;
		justify-content: space-between;
		align-items: center;
		background: var(--zb-surface-lighter-color);
		border-bottom-right-radius: 3px;
		border-bottom-left-radius: 3px;
		padding: 10px 20px;

		.znpb-loader-wrapper {
			max-width: 40px;
		}
	}

	&-title {
		color: var(--zb-surface-text-active-color);
		font-size: 13px;
		font-weight: 500;
	}

	&-actions {
		display: flex;
		align-items: center;
		& > span,
		& > a {
			margin-right: 8px;
		}

		& > span:last-of-type {
			margin-right: 0;
		}
	}

	&-action {
		.znpb-button {
			padding: 10px 18px;
		}
	}

	&-bottom-multiple {
		position: absolute;
		bottom: 0;
		left: 0;
		z-index: -1;
		width: 100%;
		height: 30px;
		background-color: rgb(255, 255, 255);
		border: 1px solid var(--zb-surface-border-color);
		border-radius: 3px;
		transform: scale(0.9) translateY(15px);
		transition: box-shadow 0.2s;

		break-inside: avoid;

		&:after {
			content: "";
			position: absolute;
			bottom: 0;
			left: 0;
			width: 100%;
			height: 30px;
			background-color: rgb(255, 255, 255);
			box-shadow: 0 4px 10px 0 rgba(164, 164, 164, 0.08);
			border: 1px solid var(--zb-surface-border-color);
			border-radius: 3px;
			transform: scale(1.07) translateY(-6px);
		}
	}

	&-bottom-actions {
		.znpb-editor-icon-wrapper {
			margin-right: 8px;
			font-size: 18px;
			cursor: pointer;

			&:last-child {
				margin-right: 0;
			}
		}
	}
	&--favorite {
		.znpb-editor-icon.zion-heart {
			path,
			path:first-child {
				fill: var(--zb-secondary-color);
			}
		}
	}
}
</style>
