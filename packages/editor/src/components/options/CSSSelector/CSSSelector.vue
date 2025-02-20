<template>
	<div
		class="znpb-option-cssSelectoritem"
		:class="{'znpb-option-cssSelectoritem--child': isChild}"
	>
		<div class="znpb-option-cssSelectorWrapper">
			<PseudoSelector
				v-if="isChild"
				v-model:states="pseudoState"
			/>

			<AccordionMenu
				:show-trigger-arrow="true"
				:has-breadcrumbs="show_breadcrumbs"
				:title="title"
				:child_options="schema"
				v-model="value"
				class="znpb-option-cssSelectorAccordion"
			>
				<template #title>
					<!-- <Icon icon="brush" /> -->
					<div>
						<div class="znpb-option-cssSelectorTitle">{{title}}</div>
						<div
							class="znpb-option-cssSelector"
							:title="selector"
						>{{selector}}</div>
					</div>
				</template>

				<template #actions>
					<AddChildActions
						v-if="allow_childs"
						:child-selectors="childSelectors"
						@add-child="onChildAdded"
						@toggle-view-childs="showChilds = !showChilds"
					/>

					<ChangesBullet
						:content="$translate('discard_changes')"
						v-if="show_changes && hasChanges"
						@remove-styles="resetChanges"
					/>

					<Tooltip
						:content="$translate('delete_selector')"
						placement="top"
						append-to="element"
						strategy="fixed"
					>
						<Tooltip
							placement="top"
							append-to="element"
							strategy="fixed"
							:show="canShow"
							trigger="click"
							class="znpb-cssSelectorDialog"
							:close-on-outside-click="true"
							@click.stop=""
						>
							<template #content>
								<div class="znpb-cssSelectorDialog__text">{{$translate('are_you_sure_you_want_to_delete_selector')}}</div>
								<div>
									<Button
										@click.stop="canShow = false"
										type="gray"
										class="znpb-button--small"
									>
										{{$translate('cancel')}}
									</Button>
									<Button
										@click.stop="deleteItem"
										type="danger"
										class="znpb-button--small"
									>
										{{$translate('delete')}}
									</Button>
								</div>
							</template>
							<Icon
								icon="delete"
								v-if="allow_delete"
								@click.stop="canShow = true"
							/>
						</Tooltip>
					</Tooltip>

				</template>

				<OptionsForm
					:schema="schema"
					v-model="value"
					class="znpb-option-cssSelectorForm"
				/>

			</AccordionMenu>

		</div>
		<div v-if="showChilds && childSelectors.length > 0">
			<Sortable
				class="znpb-admin-colors__container"
				v-model="childSelectors"
				handle=".znpb-option-cssSelectorAccordion > .znpb-horizontal-accordion__header"
				:drag-delay="0"
				:drag-treshold="10"
				:disabled="false"
				:revert="true"
				axis="vertical"
				:group="uid"
			>
				<CSSSelector
					class="znpb-option-cssChildSelectorStyles"
					v-for="(childSelector, index) in childSelectors"
					:key="index"
					:modelValue="childSelector"
					@update:modelValue="onChildUpdate(childSelector, $event)"
					:is-child="true"
					:allow_class_assignments="false"
					:allow_custom_attributes="false"
					:show_breadcrumbs="show_breadcrumbs"
				/>
			</Sortable>
		</div>
	</div>
</template>

<script>
import { computed, defineAsyncComponent, ref } from 'vue'
import { applyFilters } from '@zb/hooks'
import { translate } from '@zb/i18n'
import { generateUID } from '@zb/utils'

// Components
import AddChildActions from './AddChildActions.vue'
import PseudoSelector from './PseudoSelector.vue'

export default {
	name: 'CSSSelector',
	components: {
		AccordionMenu: defineAsyncComponent(() => import('../AccordionMenu/AccordionMenu.vue')),
		AddChildActions,
		PseudoSelector
	},
	props: {
		modelValue: {
			type: Object,
			default: {}
		},
		allow_delete: {
			type: Boolean,
			default: true
		},
		allow_childs: {
			type: Boolean,
			default: true
		},
		isChild: {
			type: Boolean,
			default: false
		},
		allow_class_assignments: {
			type: Boolean,
			default: true
		},
		allow_custom_attributes: {
			type: Boolean,
			default: true
		},
		selector: {
			type: String,
			required: false
		},
		name: {
			type: String,
			required: false
		},
		show_breadcrumbs: {
			type: Boolean,
			default: false
		},
		show_changes: {
			type: Boolean,
			default: true
		}
	},
	setup (props, { emit }) {
		const showChilds = ref(false)
		const uid = generateUID()
		const canShow = ref(false)

		const title = computed(() => {
			return props.name || props.modelValue.title || props.modelValue.id || props.selector || 'New item'
		})

		const selector = computed(() => {
			if (props.selector) {
				return props.selector
			} else if (props.modelValue.id) {
				return `.${props.modelValue.id}`
			} else if (props.modelValue.selector) {
				return props.modelValue.selector
			}
		})

		const childSelectors = computed({
			get () {
				return props.modelValue.child_styles || []
			},
			set (newValue) {
				if (null === newValue || newValue.length === 0) {
					delete value.value.child_styles
				} else {
					value.value = {
						...value.value,
						child_styles: newValue
					}
				}

			}
		})

		const pseudoState = computed({
			get () {
				return value.value.states || ['default']
			},
			set (newStateValue) {
				value.value.states = newStateValue
			}
		})

		const schema = computed(() => {
			const schema = {
				styles: {
					type: 'element_styles',
					id: 'styles',
					is_layout: true,
					selector: selector.value,
					title: title.value,
					allow_class_assignments: props.allow_class_assignments
				}
			}

			// attach the attribute options
			if (props.allow_custom_attributes) {
				schema.attributes = applyFilters('zionbuilder/options/attributes', {
					type: 'accordion_menu',
					title: 'custom attributes',
					icon: 'tags-attributes',
					is_layout: true,
					label: {
						type: translate('pro'),
						text: translate('pro')
					},
					show_title: false,
					child_options: {
						upgrade_message: {
							type: 'upgrade_to_pro',
							message_title: translate('meet_custom_attributes'),
							message_description: translate('meet_custom_attributes_desc'),
							info_text: translate('meet_custom_attributes_link')
						}
					}
				})
			}



			return schema
		})

		const hasChanges = computed(() => Object.keys(value.value.styles || {}).length > 0)

		const value = computed({
			get () {
				return props.modelValue
			},
			set (newValue) {
				emit('update:modelValue', newValue)
			}
		})

		function onChildAdded (childData) {
			childSelectors.value = [
				...childSelectors.value,
				childData
			]

			showChilds.value = true
		}

		function onChildUpdate (child, newValue) {
			const value = childSelectors.value.slice()
			const childIndex = childSelectors.value.indexOf(child)

			if (newValue === null) {
				value.splice(childIndex, 1)
			} else {
				value.splice(childIndex, 1, newValue)
			}

			childSelectors.value = value
		}

		function deleteItem () {
			emit('update:modelValue', null)
		}

		function resetChanges () {
			delete value.value.styles
		}

		return {
			canShow,
			onChildAdded,
			showChilds,
			title,
			selector,
			childSelectors,
			deleteItem,
			schema,
			value,
			onChildUpdate,
			pseudoState,
			hasChanges,
			resetChanges,
			uid
		}
	}
}
</script>

<style lang="scss">
.znpb-option-cssSelectorWrapper {
	display: flex;
	align-items: center;
	overflow: visible;
}
.znpb-option-cssSelectorForm .znpb-options-form-wrapper {
	padding: 0;
}

.znpb-option-cssSelectorAccordion > .znpb-horizontal-accordion__header > .znpb-horizontal-accordion__title {
	position: relative;
	overflow: hidden;
	padding-right: 0;
	padding-bottom: 12px;
	margin-right: 15px;
}

.znpb-option-cssSelector {
	position: absolute;
	width: 100%;
	font-size: 13px;
	font-weight: 500;
	text-transform: none;
	white-space: nowrap;
	opacity: .6;

	&::after {
		content: "";
		position: absolute;
		top: 0;
		right: 0;
		z-index: 1;
		width: 20px;
		height: 100%;
		background: linear-gradient(
		90deg,
		rgba(241, 241, 241, 0) 0%,
		var(--zb-surface-lighter-color) 100%
		);
	}
}

.znpb-option-cssSelectorTitle {
	margin-bottom: 8px;
}

.znpb-option-cssSelectoritem--child .znpb-option-cssSelectoritem--child {
	padding-left: 61px;
}

.znpb-option-cssChildSelectorPseudoSelector {
	position: relative;
	z-index: 1;
	display: flex;
	justify-content: center;
	align-items: center;
	flex: 1 0 auto;
	width: 56px;
	padding: 5px 8px;
	margin: 0 5px 5px 0;
	color: #fff;
	font-size: 11px;
	font-weight: 500;
	line-height: 1;
	background: #8bc88a;
	border-radius: 2px;
	transition: background .2s;
	cursor: pointer;

	&:hover {
		background: darken(#8bc88a, 5%);
	}

	&::before, &::after {
		content: "";
		position: absolute;
		z-index: -1;
		background: var(--zb-surface-border-color);
	}

	&::before {
		bottom: 100%;
		left: 50%;
		width: 1px;
		height: 25px;
	}

	&::after {
		top: 50%;
		left: 100%;
		width: 5px;
		height: 1px;
	}
}

.znpb-option-cssSelectoritem--child + .znpb-option-cssSelectoritem--child
	.znpb-option-cssChildSelectorPseudoSelector::before {
	height: 42px;
}

/* Child styles */
.znpb-option-cssChildSelectorStyles {
	flex-grow: 1;

	& .znpb-option-cssSelectorAccordion > .znpb-horizontal-accordion__header {
		padding: 12px;
	}

	&.vuebdnd__source--dragging
	.znpb-option-cssChildSelectorPseudoSelector:before {
		display: none;
	}
}

.znpb-option-cssSelectorAccordion {
	flex: 1 1 auto;
}

.znpb-cssSelectorDialog {
	text-align: center;

	.hg-popper {
		padding: 15px;
	}

	&__text {
		margin-bottom: 10px;
	}

	.znpb-button {
		margin: 0 2px;
	}
}
</style>