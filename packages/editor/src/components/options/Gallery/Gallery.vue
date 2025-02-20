<template>
	<div class="znpb-option__image-gallery">
		<EmptyList
			v-if="!sortableModel.length"
			@click="openMediaModal"
		>
			{{$translate('no_images_selected')}}
		</EmptyList>

		<Sortable
			v-else
			v-model="sortableModel"
			class="znpb-option__image-gallery__images-wrapper"
		>
			<div
				v-for="(image, index) in modelValue"
				:key="index"
				class="znpb-option__image-gallery__images-item"
				:style="{
					'background-image': `url(${image.image})`,
					'background-size': 'cover',
					'border-radius': '3px'
				}"
			>
				<!-- <img :src="image.image"/> -->

				<span
					class="znpb-option__image-gallery__images-item--delete"
					@click="deleteImage(index)"
				>
					<Icon
						:rounded="true"
						icon="delete"
						:bg-size="30"
						bg-color="#fff"
					/>
				</span>
			</div>
			<template v-slot:end>
				<div
					@click="openMediaModal"
					class="znpb-option__image-gallery__images-item--add"
				>+</div>
			</template>
		</Sortable>
	</div>
</template>

<script>
import { getImageIds } from '@zb/rest'

const wp = window.wp

export default {
	name: 'Gallery',
	props: {
		modelValue: {
			type: Array,
			required: false
		},
		title: {},
		/**
		 * Type of media
		 */
		type: {
			required: false,
			type: String,
			default: 'image'
		}
	},
	data () {
		return {
			mediaModal: null
		}
	},
	computed: {
		sortableModel: {
			get () {
				return this.modelValue || []
			},
			set (newValue) {
				this.$emit('update:modelValue', newValue)
			}
		}
	},

	methods: {
		openMediaModal () {
			if (this.mediaModal === null) {
				const args = {
					frame: 'select',
					state: 'zion-media',
					library: {
						type: 'image'
					},
					button: {
						text: 'Add Image'
					},
					multiple: 'add',
					selection: [518]
				}

				// Create the frame
				this.mediaModal = new window.wp.media.view.MediaFrame.ZionBuilderFrame(args)
				this.mediaModal.on('select update insert', this.selectMedia)

				// Select the images
				this.mediaModal.on('open', this.setMediaModalSelection)
			}

			// Open the media modal
			this.mediaModal.open()
		},
		setMediaModalSelection (e) {
			if (typeof this.modelValue === 'undefined') return

			// Get image ids from DB
			let imagesUrls = this.modelValue.map(image => image.image)
			getImageIds({
				images: imagesUrls
			}).then((response) => {
				const imageIds = Object.keys(response.data).map(image => {
					return response.data[image]
				})

				const selection = this.mediaModal.state().get('selection')
				imageIds.forEach((imageId) => {
					var attachment = wp.media.attachment(imageId)
					selection.add(attachment ? [attachment] : [])
				})
			})
		},
		selectMedia (e) {
			let selection = this.mediaModal.state().get('selection').toJSON()
			// In case we have multiple items
			if (typeof e !== 'undefined') { selection = e }

			const values = selection.map((selectedItem) => {
				return { image: selectedItem.url }
			})

			this.$emit('update:modelValue', values)
		},
		deleteImage (index) {
			const values = [...this.modelValue]
			values.splice(index, 1)

			this.$emit('update:modelValue', values)
		}
	}
}
</script>
<style lang="scss">
.znpb-option__image-gallery {
	padding: 0 5px;

	.znpb-empty-list__container {
		background-color: var(--zb-surface-lighter-color);
	}
	.znpb-empty-list__content {
		padding: 50px 0;
	}
	&__images-wrapper {
		display: flex;
		flex-wrap: wrap;
		margin: 0 -4px;
	}

	&__images-item {
		position: relative;
		display: flex;
		justify-content: center;
		align-items: center;
		flex: 0 0 calc((100% - 16px) / 3);
		max-width: calc((100% - 16px) / 3);
		margin: 0 8px 8px 0;
		box-shadow: 0 0 0 0 #f1f1f1;

		&::after {
			content: "";
			display: block;
			padding-top: 100%;
		}

		&--delete {
			position: absolute;
			top: 5px;
			right: 5px;
			z-index: 9;
			margin-top: 5px;
			margin-right: 5px;
			cursor: pointer;
			opacity: 0;
			visibility: hidden;
		}
		&--add {
			display: flex;
			justify-content: center;
			align-items: center;
			flex: 0 0 calc((100% - 16px) / 3);
			max-width: calc((100% - 16px) / 3);
			margin: 0 0 8px;
			color: var(--zb-surface-text-color);
			font-size: 20px;
			border: 2px dashed var(--zb-surface-border-color);
			cursor: pointer;

			&::after {
				content: "";
				display: block;
				padding-top: 100%;
			}
		}
		&:hover &--delete {
			opacity: 1;
			visibility: visible;
		}

		&:nth-child(3n) {
			margin-right: 0;
		}
	}
}
</style>
