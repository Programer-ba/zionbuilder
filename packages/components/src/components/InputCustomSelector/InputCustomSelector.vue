<template>
	<div class="znpb-custom-selector">
		<ul class="znpb-custom-selector__list-wrapper">
			<li
				v-for="(option, index) in options"
				:key="index"
				class="znpb-custom-selector__item"
				:title="option.icon ? option.name : ''"
				:class="{
					['znpb-custom-selector__item--active']: modelValue === option.id,
					[`znpb-custom-selector__columns-${columns}`]: columns
				}"
				@click="changeValue(option.id)"
			>
				<span
					class="znpb-custom-selector__item-name"
					v-if="!option.icon"
				>
					{{option.name}}
				</span>
				<Icon
					v-if="!textIcon && option.icon"
					:icon="option.icon"
				/>
				<div
					class="znpb-custom-selector__icon-text-content"
					v-if="textIcon"
				>
					<Icon
						v-if="option.icon"
						:icon="option.icon"
					/>
					<span
						class="znpb-custom-selector__item-name"
						v-if="option.name"
					>
						{{option.name}}
					</span>
				</div>
			</li>
		</ul>
	</div>
</template>

<script>
import { Icon } from '../Icon'

export default {
	name: 'InputCustomSelector',
	components: {
		Icon
	},
	props: {
		options: {
			type: Array,
			required: true
		},
		columns: {
			type: Number,
			required: false
		},
		modelValue: {
			type: [String, Number, Boolean]
		},
		textIcon: {
			type: Boolean
		}
	},
	methods: {
		changeValue (newValue) {
			let valueToSend = newValue
			// If the same value was selected, we need to delete it
			if (this.modelValue === newValue) {
				valueToSend = null
			}

			this.$emit('update:modelValue', valueToSend)
		}
	}
}
</script>

<style lang="scss">
.znpb-custom-selector {
	overflow: hidden;
	padding: 3px;
	background-color: var(--zb-surface-lighter-color);
	border-radius: 3px;

	&__list-wrapper {
		display: flex;
		flex-wrap: wrap;
		margin: 0;
	}

	&__item {
		display: flex;
		justify-content: center;
		align-items: center;
		flex: 1 1 auto;
		color: var(--zb-surface-text-color);
		padding: 10px 5px;
		margin: 0;
		font-size: 13px;
		font-weight: 500;
		border-radius: 2px;
		cursor: pointer;

		&:hover {
			color: var(--zb-surface-text-active-color);
			background-color: var(--zb-surface-lightest-color);
		}
		&--active {
			color: var(--zb-secondary-text-color);
			background-color: var(--zb-secondary-color);
			&:hover {
				color: var(--zb-secondary-text-color);
				background-color: var(--zb-secondary-color);
			}
		}
	}

	&__columns-1 {
		width: 100%;
	}
	&__columns-2 {
		width: 50%;
	}
	&__columns-3 {
		flex-basis: 33%;
		width: calc(100% / 3);
	}
	&__columns-4 {
		width: 25%;
	}
	&__icon-text-content {
		display: flex;
		flex-direction: column;
	}
}
</style>
