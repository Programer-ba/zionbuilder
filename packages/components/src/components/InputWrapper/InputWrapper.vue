<template>
	<div
		class="znpb-input-wrapper"
		:class="{
			[`znpb-input-wrapper--${layout}`]: true
		}"
		:style="computedWrapperStyle"
	>
		<div
			class="znpb-form__input-title"
			:class="{'znpb-form__input-title--fake-label': fakeLabel}"
			v-if="title"
		>
			<span v-html="title"></span>

			<Tooltip
				v-if="description"
				:enterable="false"
			>
				<template v-slot:content>
					<div> {{description}}</div>
				</template>

				<Icon icon="question-mark" />
			</Tooltip>
		</div>
		<div class="znpb-forms-input-content">
			<!-- @slot Content inside wrapper -->
			<slot></slot>
		</div>
	</div>
</template>

<script>
import { Tooltip } from '@zionbuilder/tooltip'
import { Icon } from '../Icon'
export default {
	name: 'InputWrapper',
	components: {
		Tooltip,
		Icon
	},
	props: {
		/**
		 * Title
		 */
		title: {
			type: String,
			required: false
		},
		/**
		 * Description added to tooltip
		 */
		description: {
			type: String,
			required: false
		},
		/**
		 * layout
		 */
		layout: {
			type: String,
			required: false,
			default: 'full'
		},
		/**
		 * If fake label
		 */
		fakeLabel: {
			type: Boolean,
			required: false,
			default: false
		},
		schema: {
			type: Object,
			required: false
		}
	},
	computed: {
		computedWrapperStyle () {
			const styles = {}

			if (this.schema !== undefined) {
				if (this.schema.grow) {
					styles.flex = this.schema.grow
				}

				if (this.schema.width) {
					styles.width = `${this.schema.width}%`
				}
			}

			return styles
		}
	}
}
</script>
<style lang="scss">
.znpb-input-wrapper.znpb-forms-input-wrapper {
	padding: 0 20px;
	margin-bottom: 15px;

	.znpb-forms-form__input-title {
		padding: 15px 15px 15px 0;
		color: var(--zb-surface-text-active-color);
		font-size: 14px;
		font-weight: 500;
		line-height: 1;

		&--fake-label {
			margin-bottom: 26px;
		}
	}

	&--full {
		width: 100%;
		.znpb-forms-form__input-title {
			color: var(--zb-surface-text-active-color);
		}
	}
	&--full-reverse {
		display: flex;
		flex-direction: column-reverse;
		.znpb-forms-input-content {
			margin-bottom: 5px;
		}
		.znpb-form__input-title {
			text-transform: uppercase;
		}
	}

	&--inline {
		display: flex;
		justify-content: space-between;
		align-items: center;

		.znpb-forms-form__input-title {
			flex-grow: 1;
			margin-bottom: 0;
		}

		.znpb-form__input-title {
			margin: 0;
		}

		.znpb-forms-input-content {
			width: auto;
		}

		& > .znpb-input-content {
			display: flex;
			justify-content: flex-end;
		}
	}
}
</style>
