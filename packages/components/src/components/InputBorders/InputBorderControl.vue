<template>
	<OptionsForm
		:schema="schema"
		v-model="computedValue"
		class="znpb-border-control-group"
	/>
</template>

<script>
export default {
	name: 'InputBorderControl',
	props: {
		/**
		 * v-model/modelValue for border
		 */
		modelValue: {
			default () {
				return {}
			},
			type: Object,
			required: false
		},
		/**
		 * title border
		 */
		title: {
			type: String,
			required: false
		}
	},
	data () {
		return {
			borderStyle: [
				{
					id: 'solid',
					name: 'solid'
				},
				{
					id: 'dashed',
					name: 'dashed'
				},
				{
					id: 'dotted',
					name: 'dotted'
				},
				{
					id: 'double',
					name: 'double'
				},
				{
					id: 'groove',
					name: 'groove'
				},
				{
					id: 'ridge',
					name: 'ridge'
				},
				{
					id: 'inset',
					name: 'inset'
				},
				{
					id: 'outset',
					name: 'outset'
				}
			]
		}
	},
	computed: {
		schema () {
			const schema = {
				color: {
					id: 'color',
					type: 'colorpicker',
					css_class: 'znpb-border-control-group-item',
					title: 'Color',
					width: 100
				},
				width: {
					id: 'width',
					type: 'number_unit',
					title: 'Width',
					min: 0,
					max: 999,
					units: ['px', 'rem', 'pt', 'vh', '%'],
					step: 1,
					css_class: 'znpb-border-control-group-item',
					width: 50
				},
				style: {
					id: 'style',
					type: 'select',
					title: 'Style',
					default: 'solid',
					options: this.borderStyle,
					css_class: 'znpb-border-control-group-item',
					width: 50
				}
			}

			return schema
		},

		computedValue: {
			get () {
				return this.modelValue || {}
			},
			set (newValue) {
				this.$emit('update:modelValue', newValue)
			}
		}
	}
}
</script>

<style lang="scss">
.znpb-input-wrapper.znpb-border-control-group-item {
	padding-bottom: 0;
}

.znpb-input-wrapper.znpb-border-control-group-item.znpb-input-type--colorpicker {
	margin: 0 0 20px 0;
}

.znpb-border-control-group-item .znpb-global-color-select-innerWrapper {
	justify-content: flex-start;
}

.znpb-border-control-group-item .znpb-global-color-select-tooltip {
	flex: 1;
	padding-right: 10px;
}

.znpb-border-control-group-item
	.znpb-global-color-select-innerWrapper
	> span:last-child {
	margin-left: auto;
}

.znpb-border-control-group-item .znpb-global-color-select__id {
	font-weight: 500;
	width: auto;
}
</style>
