<template>
	<ElementLoading v-if="loading" />

	<component
		v-else-if="elementComponent && !(element.isVisible === false && isPreviewMode)"
		:is="elementComponent"
		class="znpb-element__wrapper zb-element"
		:class="{
			'znpb-element__wrapper--panel-hovered': element.isHighlighted
		}"
		:id="`${element.elementCssId}`"
		:element="element"
		:options="options"
		@mouseenter="showToolbox = true"
		@mouseleave="onMouseLeave"
		@click="onElementClick"
		@dblclick="editElement"
		@contextmenu="showElementMenu"
		v-bind="getExtraAttributes"
	>

		<template #start>
			<ElementToolbox
				v-if="canShowToolbox"
				:element="element"
				v-model:can-hide-toolbox="canHideToolbox"
				v-model:is-toolbox-dragging="isToolboxDragging"
			/>

			<VideoBackground
				v-if="videoConfig"
				:video-config="videoConfig"
			/>

			<ElementStyles :styles="customCSS" />
		</template>

		<template #end>
			<div
				class="znpb-hidden-element-container"
				v-if="!element.isVisible"
			>
				<div class="znpb-hidden-element-placeholder">
					<Icon
						icon="eye"
						@click.stop="element.toggleVisibility()"
					>
					</Icon>
				</div>
			</div>
		</template>
	</component>
</template>

<script>
// Utils
import { ref, watch, computed, readonly, provide } from 'vue'
import { get, debounce, each, kebabCase, escape, mergeWith, isArray } from 'lodash-es'
import { getCssFromSelector, getOptionValue, camelCase, clearTextSelection } from '@zb/utils'
import { applyFilters } from '@zb/hooks'

// Components
import ElementToolbox from './ElementToolbox/ElementToolbox.vue'
import ElementStyles from './ElementStyles.vue'
import ElementLoading from './ElementLoading.vue'
import VideoBackground from './VideoBackground.vue'

// Composables
import { usePreviewMode, useElementMenu, useElementActions, useEditElement } from '@zb/editor'
import { useElementComponent } from '@composables'
import Options from '../Options'
import { useOptionsSchemas, usePseudoSelectors } from '@zb/components'

let handled = false

export default {
	name: 'ElementWrapper',
	components: {
		ElementToolbox,
		VideoBackground,
		ElementLoading,
		ElementStyles
	},
	props: ['element'],
	setup (props) {
		const { isPreviewMode } = usePreviewMode()
		const { elementComponent, fetchElementComponent } = useElementComponent(props.element)
		const { focusElement } = useElementActions()
		const { getSchema } = useOptionsSchemas()
		const { activePseudoSelector } = usePseudoSelectors()
		const { element: activeEditedElement } = useEditElement()

		let toolboxWatcher = null
		let optionsInstance = null

		// Data
		const localLoading = ref(false)
		const loading = computed(() => props.element.loading || localLoading.value)
		const showToolbox = ref(false)
		const canHideToolbox = ref(true)
		const isToolboxDragging = ref(false)
		const registeredEvents = ref({})

		// Needed to generate the hover css
		const isElementEdited = ref(false)
		const isHoverState = ref(false)

		// Options schema
		const advancedSchema = {
			_advanced_options: {
				type: 'group',
				child_options: getSchema('element_advanced')
			}
		}

		const elementOptionsSchema = Object.assign({}, get(props.element, 'elementTypeModel.options', {}), advancedSchema)

		// computed
		const parsedData = computed(() => {
			const cssSelector = `#${props.element.elementCssId}`

			optionsInstance = new Options(
				elementOptionsSchema,
				props.element.options,
				cssSelector,
				{
					onLoadingStart: () => localLoading.value = true,
					onLoadingEnd: () => localLoading.value = false,
				},
				props.element
			)

			return optionsInstance.parseData()
		})

		const options = computed(() => readonly(parsedData.value.options || {}))

		// Check to see if the current element is being edited
		watch(activeEditedElement, (newValue, oldValue) => {
			if (newValue === props.element) {
				isElementEdited.value = true
			} else if (oldValue === props.element) {
				isElementEdited.value = false
			}
		})

		// check to see if the hover state is selected
		watch(activePseudoSelector, (newValue) => {
			if (newValue.id === ':hover') {
				isHoverState.value = true
			} else {
				isHoverState.value = false
			}
		})

		const shouldGenerateHoverStyles = computed(() => {
			return isElementEdited.value && isHoverState.value
		})

		const customCSS = computed(() => {
			let customCSS = parsedData.value.customCSS
			const elementStyleConfig = props.element.elementTypeModel.style_elements

			if (elementStyleConfig) {
				Object.keys(elementStyleConfig).forEach(styleId => {
					if (options.value._styles && options.value._styles[styleId]) {
						const styleConfig = elementStyleConfig[styleId]
						const cssSelector = applyFilters('zionbuilder/element/css_selector', `#${props.element.elementCssId}`, optionsInstance, props.element)
						const formattedSelector = styleConfig.selector.replace('{{ELEMENT}}', cssSelector)
						const stylesSavedValues = applyFilters('zionbuilder/element/styles_model', options.value._styles[styleId], optionsInstance, props.element)

						customCSS += getCssFromSelector([formattedSelector], stylesSavedValues)

						// Generate the styles on hover
						if (shouldGenerateHoverStyles.value) {
							customCSS += getCssFromSelector([formattedSelector], stylesSavedValues, {
								forcehoverState: true
							})
						}
					}
				})
			}

			customCSS = applyFilters('zionbuilder/element/custom_css', customCSS, optionsInstance, props.element)

			return customCSS
		})

		const stylesConfig = computed(() => options.value._styles || {})
		const canShowToolbox = computed(() => props.element.isVisible && showToolbox.value && !isPreviewMode.value && !props.element.elementTypeModel.is_child)
		const canShowElement = computed(() => isPreviewMode.value ? !(options.value._isVisible === false) : true)
		const videoConfig = computed(() => getOptionValue(options.value, '_styles.wrapper.styles.default.default.background-video', {}))


		const renderAttributes = computed(() => {
			const optionsAttributes = parsedData.value.renderAttributes
			const additionalAttributes = {}

			if (stylesConfig.value) {
				each(stylesConfig.value, (styleData, styleID) => {
					if (styleData.attributes) {
						each(styleData.attributes, (attributeValue) => {
							if (attributeValue.attribute_name) {
								additionalAttributes[styleID] = additionalAttributes[styleID] || {}

								let cleanAttrName = kebabCase(attributeValue.attribute_name)
								let cleanAttrValue = escape(attributeValue.attribute_value)
								additionalAttributes[styleID][cleanAttrName] = cleanAttrValue
							}
						})

					}
				})
			}

			// Check for custom css classes
			const elementStyleConfig = props.element.elementTypeModel.style_elements
			if (elementStyleConfig) {
				Object.keys(elementStyleConfig).forEach(styleId => {
					if (options.value._styles && options.value._styles[styleId] && options.value._styles[styleId].classes) {
						const styleConfig = elementStyleConfig[styleId]
						const renderTag = styleConfig.render_tag

						if (renderTag) {
							options.value._styles[styleId].classes.forEach(cssClass => {

								if (!additionalAttributes[renderTag]) {
									additionalAttributes[renderTag] = {}
								}


								additionalAttributes[renderTag]['class'] = [
									...(additionalAttributes[renderTag]['class'] || []),
									cssClass
								]
							})
						}

					}
				})
			}

			return mergeWith(
				{},
				optionsAttributes,
				additionalAttributes,
				(a, b) => {
					if (isArray(a)) {
						return b.concat(a);
					}
				}
			)
		})
		const getExtraAttributes = computed(() => {
			const wrapperAttributes = renderAttributes.value.wrapper || {}

			const elementClass = camelCase(props.element.element_type)
			const classes = applyFilters(
				'zionbuilder/element/css_classes',
				{
					[`zb-el-${elementClass}`]: true,
					[`znpb-element__wrapper--toolbox-dragging`]: isToolboxDragging.value,
					'znpb-element__wrapper--cutted': props.element.isCutted,
					'znpb-element--loading': loading.value
				},
				optionsInstance,
				props.element
			)


			if (stylesConfig.value.wrapper) {
				const wrapperConfig = stylesConfig.value.wrapper
				if (wrapperConfig.classes) {

					wrapperConfig.classes.forEach(classSelector => {
						classes[classSelector] = true
					})
				}
			}

			// Add classes added by render attributes
			const wrapperClasses = typeof wrapperAttributes.class !== 'undefined' ? wrapperAttributes.class : []

			wrapperClasses.forEach(cssClass => {
				classes[cssClass] = true
			})

			// Add render attributes classes
			return {
				...wrapperAttributes,
				class: classes,

				api: {
					getStyleClasses,
					getAttributesForTag
				}
			}
		})

		// Get the element component
		fetchElementComponent()

		function getAttributesForTag (tagID, extraArgs = {}, index = null) {
			tagID = index !== null ? `${tagID}${index}` : tagID
			return Object.assign(renderAttributes.value[tagID] || {}, extraArgs)
		}

		/**
		 * On context menu open
		 */
		const showElementMenu = function (event) {
			if (!isPreviewMode.value) {
				event.preventDefault()
				event.stopPropagation()

				const { showElementMenuFromEvent } = useElementMenu()
				showElementMenuFromEvent(props.element, event, {
					rename: false
				})
			}

		}

		// Prevents us using stop propagation that can affect other elements
		const onElementClick = (event) => {
			if (handled) {
				return
			}

			focusElement(props.element)

			handled = true
			setTimeout(() => {
				handled = false
			}, 10);
		}

		/**
		 * On element mouseleave
		 */
		const onMouseLeave = () => {
			if (canHideToolbox.value) {
				showToolbox.value = false
			} else {
				if (!toolboxWatcher) {
					// Set a watcher so we can hide the toolbox
					toolboxWatcher = watch(canHideToolbox, (newValue) => {
						if (newValue) {
							showToolbox.value = false
							toolboxWatcher()
							toolboxWatcher = null
						}
					})
				}
			}
		}

		const getStyleClasses = (styleId, extraClasses = {}) => {
			const classes = {}

			if (stylesConfig.value[styleId]) {
				const elementStylesClasses = stylesConfig.value[styleId]
				if (elementStylesClasses.classes) {
					elementStylesClasses.classes.forEach(classSelector => {
						classes[classSelector] = true
					})
				}
			}

			return classes
		}

		// Mainly used for RenderValue component
		provide('elementInfo', props.element)
		provide('elementOptions', options)

		return {
			// Computed
			stylesConfig,
			canShowToolbox,
			canShowElement,
			videoConfig,
			getExtraAttributes,
			// Data
			elementComponent,
			isPreviewMode,
			showElementMenu,
			focusElement,
			onElementClick,
			options,
			customCSS,
			loading,
			canHideToolbox,
			isToolboxDragging,
			toolboxWatcher,
			registeredEvents,
			showToolbox,
			// Methods
			onMouseLeave
		}
	},
	watch: {
		'element.scrollTo' (newValue) {
			if (newValue) {
				if (typeof this.$el.scrollIntoView === 'function') {
					this.$el.scrollIntoView({
						behavior: 'smooth'
					})
				}

				setTimeout(() => {
					this.element.scrollTo = false
				}, 1000)
			}
		}
	},
	methods: {
		debounceUpdate: debounce(function () {
			this.$nextTick(() => {
				this.trigger('updated')
			})
		}),

		applyCustomClassesToRenderTags () {
			const elementSavedStyles = getOptionValue(this.options, '_styles', {})
			const stylesConfig = this.element.elementTypeModel.style_elements
			const attrConfig = getOptionValue(this.options, 'attributes', {})

			Object.keys(elementSavedStyles).forEach(styleConfigId => {
				const { classes } = elementSavedStyles[styleConfigId]

				if (typeof stylesConfig[styleConfigId] !== 'undefined') {
					const { render_tag: renderTag } = stylesConfig[styleConfigId]
					if (renderTag && classes && classes.length > 0) {
						classes.forEach(cssClass => {
							this.element.renderAttributes.addRenderAttribute(renderTag, 'class', cssClass)
						})
					}
				}
			})
		},

		/**
		 * Edit element
		 *
		 * Triggered when double click on the element
		 */
		editElement (event) {
			const { editElement } = useEditElement()
			event.stopPropagation()

			if (!this.isPreviewMode.value) {
				editElement(this.element)

				// Clear text selection that may appear
				clearTextSelection(window)
				clearTextSelection(window.parent)
			}
		},

		restoreHiddenElement () {
			this.element.toggleVisibility()
		},
		/**
		 * Register an event for an action
		 */
		on (eventType, callback) {
			if (typeof this.registeredEvents[eventType] === 'undefined') {
				this.registeredEvents[eventType] = []
			}

			this.registeredEvents[eventType].push(callback)
		},
		/**
		 * Remove an event listener
		 */
		off (eventType, callback) {
			if (typeof this.registeredEvents[eventType] === 'undefined' && this.registeredEvents[eventType].includes(callback)) {
				const callbackIndex = this.registeredEvents[eventType].indexOf(callback)
				if (callbackIndex !== -1) {
					this.registeredEvents[eventType].splice(callbackIndex, 1)
				}
			}
		},
		/**
		 * Remove all events
		 */
		offAll () {
			this.registeredEvents = {}
		},
		getDefaultEventResponse () {
			return {
				elementType: this.element.element_type,
				element: this.$el,
				options: this.options || {},
				elementUid: this.element.uid,

				// Actions that the user can subscribe to
				on: this.on,
				off: this.off,
				offAll: this.offAll
			}
		},
		trigger (eventType, data) {
			const defaultData = this.getDefaultEventResponse()
			if (typeof this.registeredEvents[eventType] !== 'undefined') {
				this.registeredEvents[eventType].forEach(callbackFunction => {
					callbackFunction({
						...defaultData,
						...data
					})
				})
			}
		}

	}
}
</script>

<style lang="scss">
.znpb-element {
	&--loading {
		opacity: .2;
	}

	&--needs-data {
		padding: 10px;
		color: #4e4e4e;
		text-align: center;
		background: #eaeaea;
		border: 1px solid #e0e0e0;
	}
}

.znpb-element__wrapper--cutted {
	opacity: .2;
	pointer-events: none;
}
@keyframes znpb-scale-down {
	0% {
		transform: scale(1.05);
		opacity: 0;
	}
	100% {
		transform: scale(1);
		opacity: 1;
	}
}

.znpb-element__wrapper--panel-hovered {
	box-shadow: 0 0 0 2px rgba(var(--zb-secondary-rgb-color), .3);
}

.znpb-element__wrapper {
	position: relative;
	transition: opacity .2s;

	.znpb-hidden-element-container {
		position: absolute;
		top: 0;
		right: 0;
		bottom: 0;
		left: 0;
		display: flex;
		justify-content: center;
		align-items: center;
		width: 100%;
		height: 100%;
		background: rgba(255, 255, 255, .7);
	}

	&:hover, &--toolbox-dragging {
		position: relative;
	}

	&:hover {
		cursor: move;
	}

	.znpb-hidden-element-placeholder {
		position: relative;
		display: flex;
		justify-content: center;
		align-items: center;
		width: 34px;
		height: 34px;
		font-size: 18px;
		&:before {
			content: "";
			position: absolute;
			top: 0;
			left: 0;
			width: 100%;
			height: 100%;
			background: var(--zb-red);
			box-shadow: 0 11px 20px 0 rgba(0, 0, 0, .1);
			border-radius: 50%;
			transition: all .2s;
		}
		.znpb-editor-icon-wrapper {
			position: relative;
			color: var(--zb-surface-color);
			cursor: pointer;
		}
		&:hover {
			&:before {
				transform: scale(1.1);
			}
		}
	}
}
.znpb-element-utilities__margin-top-helper, .znpb-element-utilities__margin-right-helper, .znpb-element-utilities__margin-bottom-helper, .znpb-element-utilities__margin-left-helper {
	position: absolute;
}

.znpb-element-utilities__margin-top-helper, .znpb-element-utilities__margin-bottom-helper {
	left: 0;
	width: 100%;
	min-height: 2px;
	cursor: n-resize;
}

.znpb-element-utilities__margin-left-helper, .znpb-element-utilities__margin-right-helper {
	top: 0;
	width: 2px;
	height: 100%;
	cursor: e-resize;
}

.znpb-element-utilities__margin-top-helper {
	top: 0;
}
.znpb-element-utilities__margin-bottom-helper {
	bottom: 0;
}
.znpb-element-utilities__margin-left-helper {
	left: 0;
}
.znpb-element-utilities__margin-right-helper {
	right: 0;
}
.znpb-element-utilities__options {
	&-wrapper {
		position: absolute;
		top: 0;
		left: 0;
		display: flex;
		color: #fff;
		background: blue;
	}
	&-item {
		padding: 10px;
	}
}
</style>
