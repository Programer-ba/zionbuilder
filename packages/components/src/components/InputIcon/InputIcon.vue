<template>
	<div class="znpb-icon-options">
		<div
			class="znpb-icon-trigger"
			@click.self="open"
			:class="{['znpb-icon-trigger--no-icon']: !modelValue}"
		>
			<div
				v-if="modelValue"
				class="znpb-icon-options__delete"
			>
				<span
					@click.passive.stop="showModal=true"
					class="znpb-icon-preview"
					:data-znpbiconfam="modelValue.family"
					:data-znpbicon="unicode(modelValue.unicode)"
				>
				</span>
				<Icon
					@click.stop="$emit('update:modelValue',null)"
					icon="delete"
					:rounded="true"
				/>
			</div>
			<span
				v-else
				@click="showModal=true"
			>
				{{$translate('select_icon')}}
			</span>
		</div>
		<Modal
			v-model:show="showModal"
			:width="590"
			:fullscreen="false"
			append-to=".znpb-center-area"
			:show-maximize="false"
			class="znpb-icon-library-modal"
			:title="$translate('icon_library_title')"
		>
			<IconsLibraryModalContent
				v-model="valueModel"
				@selected="$emit('update:modelValue',valueModel)"
				:special-filter-pack="specialFilterPack"
			/>
		</Modal>
	</div>

</template>

<script>
import IconsLibraryModalContent from './IconsLibraryModalContent.vue'

export default {
	name: 'IconLibrary',
	components: {
		IconsLibraryModalContent
	},
	props: {
		specialFilterPack: {
			type: Array,
			required: false
		},
		title: {
			type: String,
			required: true
		},
		modelValue: {
			required: false
		}
	},
	data () {
		return {
			showModal: false,
		}
	},
	computed: {
		valueModel: {
			get () {
				return this.modelValue || {}
			},
			set (newValue) {
				this.$emit('update:modelValue', newValue)
			}
		}
	},
	methods: {
		unicode (unicode) {
			return JSON.parse(('"\\' + unicode + '"'))
		},
		open () {
			this.showModal = true
		}
	}
}
</script>
<style lang="scss">
.znpb-icon-options {
	width: 100%;
	padding: 0 5px 20px;
}
.znpb-icon-trigger {
	position: relative;
	display: flex;
	justify-content: center;
	align-items: center;
	height: 100px;
	font-weight: 500;
	background-color: var(--zb-surface-lighter-color);
	border-radius: 3px;
	cursor: pointer;

	&--no-icon {
		background: transparent;
		border: 2px dashed var(--zb-surface-border-color);
	}

	.znpb-editor-icon-wrapper {
		position: absolute;
		right: 15px;
		bottom: 15px;
		background: var(--zb-surface-color);
		padding: 7px;
		transition: all 0.2s;
		&:hover {
			opacity: 0.7;
		}
	}
}
.znpb-icon-preview {
	color: var(--zb-surface-text-active-color);
	font-size: 28px;
}
.znpb-icon-library-modal > .znpb-modal__wrapper {
	width: 100%;
}
</style>
