<template>
	<div
		class="znpb-option-repeater-no-accordion"
		v-if="!isAccordion"
	>
		<OptionsForm
			:schema="schema"
			:modelValue="selectedOptionModel"
			@update:modelValue="onItemChange($event, propertyIndex)"
			class="znpb-option-repeater-form"
		/>
		<Icon
			v-if="deletable"
			class="znpb-option-repeater-selector__delete-icon"
			@click.stop="deleteOption(propertyIndex)"
			icon="delete"
		></Icon>
	</div>
	<HorizontalAccordion
		v-else
		:title="title"
		:combine-breadcrumbs="true"
		:show-back-button="true"
	>
		<template v-slot:actions>
			<Icon
				v-if="clonable"
				class="znpb-option-repeater-selector__clone-icon"
				@click.stop="cloneOption"
				icon="copy"
			></Icon>
			<Icon
				v-if="deletable"
				class="znpb-option-repeater-selector__delete-icon"
				@click.stop="deleteOption(propertyIndex)"
				icon="delete"
			></Icon>
		</template>

		<OptionsForm
			:schema="schema"
			:modelValue="selectedOptionModel"
			@update:modelValue="onItemChange($event, propertyIndex)"
			class="znpb-option-repeater-form"
		/>
	</HorizontalAccordion>

</template>

<script>
export default {
	name: 'RepeaterOption',
	data () {
		return {
			folded: true
		}
	},
	props: {
		modelValue: {
			default () {
				return {}
			}
		},
		schema: {
			type: Object,
			required: true,
			default () {
				return []
			}
		},
		propertyIndex: {
			type: Number
		},
		item_title: {
			type: String,
			required: false
		},
		default_item_title: {
			type: String,
			required: true
		},
		deletable: {
			type: Boolean,
			required: false,
			default: true
		},
		clonable: {
			type: Boolean,
			required: false,
			default: true
		},
		isAccordion: {
			type: Boolean,
			required: false,
			default: true
		}
	},
	computed: {
		selectedOptionModel: {
			get () {
				return this.modelValue
			},
			set (newValue) {
				this.$emit('update:modelValue', newValue)
			}
		},
		title () {
			if (this.item_title && this.selectedOptionModel && this.selectedOptionModel[this.item_title]) {
				return this.selectedOptionModel[this.item_title]
			}

			return this.default_item_title.replace('%s', this.propertyIndex)
		}
	},
	methods: {
		cloneOption () {
			const clone = JSON.parse(JSON.stringify(this.modelValue))
			this.$emit('clone-option', clone)
		},
		deleteOption (propertyIndex) {
			this.$emit('delete-option', propertyIndex)
		},
		toggleOptions () {
			this.folded = !this.folded
		},
		onItemChange (newValues, index) {
			this.$emit('update:modelValue', { newValues, index })
		},
		expand () {
			this.folded = false
		},
		collapse () {
			this.folded = true
		}
	}
}
</script>

<style lang="scss">
.znpb-options-form-wrapper.znpb-option-repeater-form {
	padding-top: 0;
}
.znpb-option-repeater-no-accordion {
	display: flex;
	align-items: center;
	margin-bottom: 10px;

	.znpb-option-repeater-form {
		padding: 0;
	}
	.znpb-option-repeater-selector__delete-icon {
		padding: 11px;
		border: 2px solid var(--zion-border-color);
		border-radius: 3px;
		cursor: pointer;
	}

	.znpb-input-type--number_unit {
		padding-bottom: 0;
		padding-left: 0;
	}
}
</style>
