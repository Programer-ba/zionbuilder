<template>
	<div class="znpb-link-optionsAttribute">
		<div class="znpb-link-optionsAttributeInput znpb-link-optionsAttributeField">
			<BaseInput
				type="text"
				:placeholder="$translate('attribute_key')"
				:modelValue="attributeConfig.key"
				@update:modelValue="updateValue('key', $event)"
				:spellcheck="false"
			/>
		</div>
		<div class="znpb-link-optionsAttributeInput znpb-link-optionsAttributeField">
			<BaseInput
				type="text"
				:placeholder="$translate('attribute_value')"
				:modelValue="attributeConfig.value"
				@update:modelValue="updateValue('value', $event)"
				:spellcheck="false"
			/>
		</div>
		<div class="znpb-link-optionsAttributeDelete znpb-link-optionsAttributeField">
			<Icon
				icon="delete"
				@click="$emit('delete', attributeConfig)"
				:class="{'znpb-link-optionsAttributeDelete--disabled': !canDelete}"
			/>
		</div>
	</div>
</template>

<script>
import { BaseInput } from '@zb/components'

export default {
	name: 'LinkAttributeForm',
	components: {
		BaseInput
	},
	props: {
		attributeConfig: {
			type: Object
		},
		canDelete: {
			type: Boolean,
			required: false,
			default: true
		}
	},
	setup (props, { emit }) {
		function updateValue (key, value) {
			emit('update-attribute', {
				...props.attributeConfig,
				[key]: value
			})
		}

		return {
			updateValue
		}
	}
}
</script>

<style lang="scss">
.znpb-link-optionsAttribute {
	display: flex;
	align-items: center;
}

.znpb-link-optionsAttributeField {
	margin-bottom: 5px;
	margin-left: 5px;

	&:first-child {
		margin-left: 0;
	}
}

.znpb-link-optionsAttributeDelete {
	.znpb-editor-icon-wrapper {
		width: 40px;
		height: 40px;
		border: 2px solid var(--zb-surface-border-color);
		border-radius: 3px;
		transition: opacity 0.15s ease;
		cursor: pointer;

		&:hover {
			opacity: 0.7;
		}
	}
}

.znpb-link-optionsAttributeDelete--disabled {
	cursor: default;
	opacity: 0.5;
	pointer-events: none;

	&:hover {
		opacity: 0.5;
	}
}
</style>