
<script>
import { computed, ref, onMounted, h, Fragment, watch, nextTick, cloneVNode } from 'vue'

import {
	HostsManager,
	EventsManager
} from '../../manager'
import {
	closest
} from '../../utils'
import {
	EventScheduler,
	MoveEvent,
	StartEvent,
	EndEvent,
	ChangeEvent,
	DropEvent
} from '../../events'
import memoizeOne from 'memoize-one'
import { getBox } from 'css-box-model'

const hosts = HostsManager()
const eventsManager = EventsManager()

const getOffset = (currentDocument) => {
	// This is not needed anymore
	const frameElement = hosts.getIframes().find((iframe) => {
		return iframe.contentDocument === currentDocument
	})

	if (undefined !== frameElement) {
		const { left, top } = frameElement.getBoundingClientRect()

		return {
			left,
			top
		}
	}

	return {
		left: 0,
		top: 0
	}
}

const memoizedGetOffset = memoizeOne(getOffset)

// Finish callback
export default {
	name: 'Sortable',
	props: {
		modelValue: {
			required: false,
			type: Array,
			default () {
				return []
			}
		},
		allowDuplicate: {
			type: Boolean,
			default: false
		},
		duplicateCallback: {
			type: Function
		},
		tag: {
			type: String,
			required: false,
			default: 'div'
		},
		dragTreshold: {
			type: Number,
			required: false,
			default: 5
		},
		dragDelay: {
			type: Number,
			required: false,
			default: 0
		},
		handle: {
			type: String,
			required: false,
			default: null
		},
		draggable: {
			type: String,
			required: false,
			default: '> *'
		},
		disabled: {
			type: Boolean,
			required: false,
			default: false
		},
		group: {
			type: [String, Object, Array],
			required: false,
			default: null
		},
		sort: {
			type: Boolean,
			required: false,
			default: true
		},
		placeholder: {
			type: Boolean,
			required: false,
			default: true
		},
		cssClasses: {
			type: Object,
			required: false,
			default () {
				return {}
			}
		},
		revert: {
			type: Boolean,
			required: false,
			default: true
		},
		axis: {
			type: String,
			required: false,
			default: null
		},
		preserveLastLocation: {
			type: Boolean,
			required: false,
			default: true
		}
	},
	setup (props, { slots, emit }) {
		let duplicateValue = false
		let draggedItem = null
		let dragItemInfo = null
		let dragDelayCompleted = null
		let dimensions = null
		let initialX = null
		let initialY = null
		let currentDocument = null
		let helperNode = null
		let placeholderNode = null
		let dragTimeout = null
		let eventScheduler = null
		let hasHelperSlot = false
		let childItems = []
		let hasPlaceholderSlot = false
		let lastEvent = null
		let offset = null

		// Reactivity items
		const sortableContainer = ref(null)
		const dragging = ref(null)
		const sortableItems = ref([])
		const helper = ref(null)
		const placeholder = ref(null)
		const test = ref([])

		// Computed
		const canShowEmptyPlaceholder = computed(() => sortableItems.value.length === 0 || (dragging.value && sortableItems.value.length === 1))
		const computedCssClasses = computed(() => {
			const defaultClasses = {
				// Body when dragging
				'body': 'vuebdnd-draggable--active',
				// Element that initialised dragging
				'source': 'vuebdnd__source--dragging',
				// Container from which the draggable started
				'source:container': 'vuebdnd__source-container--dragging',
				// Helper that follows the mouse
				'helper': 'vuebdnd__helper',
				// Placeholder that displays the position of dragged element
				'placeholder': 'vuebdnd__placeholder',
				// Container that the mouse is currently hovering
				'placeholder:container': 'vuebdnd__placeholder-container'
			}

			return {
				...defaultClasses,
				...props.cssClasses
			}

		})

		const groupInfo = computed(() => {
			let group = props.group

			if (!group || typeof group !== 'object') {
				group = {
					name: group
				}
			}

			return group
		})

		// Methods
		const extractSortableItems = (slotContent) => {
			const items = []
			if (Array.isArray(slotContent)) {
				slotContent.forEach(element => {
					if (element.type === Fragment) {
						const fragmentItems = extractSortableItems(element.children)
						items.push(...fragmentItems)
					} else {
						items.push(element.el)
					}
				})
			}

			return items
		}

		const getCssClass = (cssClass) => {
			return computedCssClasses.value[cssClass] || null
		}

		/**
		 * Checks if the dragged item can be placed inside the current container
		 * @param {object} dragItemInfo The drag item info.
		 * @returns {boolean} If the dragged item can be placed inside current container
		 */
		const canPut = (dragItemInfo) => {
			const dragGroupInfo = dragItemInfo.group
			const sameGroup = dragGroupInfo.value.name === groupInfo.value.name
			const put = dragGroupInfo.put || null

			if (put === null && sameGroup) {
				return true
			} else if (put === null || put === false) {
				return false
			} else if (typeof put === 'function') {
				return put(dragItemInfo, groupInfo)
			} else {
				if (put === true) {
					return true
				} else if (typeof put === 'string') {
					return put === dragGroupInfo.value.name
				} else if (Array.isArray(put)) {
					return put.indexOf(dragGroupInfo.value.name) > -1
				}
			}

			return false
		}

		const movePlaceholder = (container, element, before) => {
			// Check to see if we need to remove the nodes
			if (null === container && element === null, before === null) {
				if (dragItemInfo.lastContainer !== container) {
					removeCssClass('placeholder:container')
					if (props.placeholder) {
						placeholderNode.remove()
					}
					dragItemInfo.lastContainer = null
				}
			} else {
				// Remove css class from last container
				if (dragItemInfo.lastContainer !== container) {
					removeCssClass('placeholder:container')
				}
				// Move placeholder if we are allowed to move it
				if (props.placeholder) {
					container.insertBefore(placeholderNode, element)
				}

				if (dragItemInfo.lastContainer !== container) {
					addCssClass('placeholder:container')
				}

				const {
					container: from,
					item,
					index:
					startIndex,
					to,
					newIndex,
					toItem
				} = dragItemInfo

				// Trigger change
				const changeEvent = new ChangeEvent({
					from,
					item,
					startIndex,
					to,
					newIndex,
					toItem,
					before
				})

				dragItemInfo.lastContainer = container

				// Send the event
				emit('change', changeEvent)
			}


		}


		/**
		 * Prevent HTML 5 DragAndDrop
		 */
		const onDragStart = (event) => {
			event.preventDefault()
		}

		const getEvents = () => {
			return {
				onStart: [onDragStart],
				onMove: onMouseMove
			}
		}

		function onMouseDown (event) {
			// Don't proceed if the event was already handled
			if (eventsManager.isHandled()) {
				return
			}

			// Check if we should start the dragg event
			if (event.button !== 0 || event.ctrlKey || event.metaKey) {
				return
			}

			// Don't proceed if we are on an editable content element
			if (event.target.isContentEditable) {
				return
			}

			// Check handle
			draggedItem = closest(event.target, props.draggable, sortableContainer.value)


			// Don't proceed if the dragged item is not part of sortable nodes
			const sortableDomElements = getDomeElementsFromSortableItems()

			if (!draggedItem || !sortableDomElements.includes(draggedItem)) {
				return
			}

			// Check if we have a handle and it was clicked
			if (props.handle && !closest(event.target, props.handle)) {
				return
			}

			// Get information about dragged item
			dragItemInfo = getInfoFromTarget(draggedItem)

			// Don't proceed if we cannot pull the item
			if (!canPull()) {
				return
			}

			// Flag for drag delay
			dragDelayCompleted = !props.dragDelay

			if (props.dragDelay) {
				clearTimeout(dragTimeout)
				dragTimeout = setTimeout(() => {
					dragDelayCompleted = true
				}, props.dragDelay)
			}

			// Set dimensions
			dimensions = getBox(draggedItem)

			// Set initial position
			const { clientX, clientY } = event
			initialX = clientX
			initialY = clientY

			// Set current document so we know if we start from iframe or not
			currentDocument = event.view.document

			hosts.fetchHosts()

			hosts.getHosts().forEach((host) => {
				host.addEventListener('mousemove', onDraggableMouseMove)
				host.addEventListener('mouseup', finishDrag)
			})

			eventsManager.handle()
		}

		const disableDraggable = () => {
			draggableHandle.removeEventListener('mousedown', attachEvents)
		}

		const detachEvents = () => {
			hosts.getHosts().forEach((host) => {
				host.removeEventListener('mousemove', onDraggableMouseMove)
				host.removeEventListener('mouseup', finishDrag)
			})

			eventsManager.reset()
		}

		const startDrag = (event) => {
			// Trigger move event
			const startEvent = new StartEvent(dragItemInfo)

			// Send the event
			emit('start', startEvent)

			if (startEvent.isCanceled()) {
				// Finish dragging
				finishDrag()
				return
			}

			currentDocument.body.style.userSelect = 'none'

			// Dimensions
			const { marginBox, paddingBox } = dimensions

			// Attach placeholder if we are not cloning it
			attachPlaceholder()

			// Attach Helper
			attachHelper()

			// Add css classes
			addCssClass('body')
			addCssClass('source')
			addCssClass('source:container')
			addCssClass('placeholder:container')

			// Make transition faster
			helperNode.style.willChange = 'transform'
			helperNode.style.zIndex = 99999
			helperNode.style.pointerEvents = 'none'
			helperNode.style.position = 'fixed'

			// Only set dimensions if the helper is not user generated
			if (hasHelperSlot) {
				draggedItem.style.display = 'none'
				const { width, height } = helperNode.getBoundingClientRect()
				helperNode.style.left = `${initialX - width / 2}px`
				helperNode.style.top = `${initialY - height / 2}px`
			} else {
				if (groupInfo.value.pull !== 'clone') {
					helperNode.style.left = `${marginBox.left}px`
				}

				helperNode.style.top = `${marginBox.top}px`
				helperNode.style.width = `${paddingBox.width}px`
				helperNode.style.height = `${paddingBox.height}px`
			}
		}

		const applyCssClass = (type, action) => {
			const cssClass = getCssClass(type)
			let node = null

			// Don't proceed if we do not have a valid class
			if (!cssClass) {
				return
			}

			if (type === 'body') {
				node = currentDocument.body
			} else if (type === 'helper') {
				node = helperNode
			} else if (type === 'placeholder') {
				node = placeholderNode
			} else if (type === 'source') {
				node = draggedItem
			} else if (type === 'source:container') {
				node = draggedItem.parentNode
			} else if (type === 'placeholder:container') {
				node = placeholderNode.parentNode
			}

			if (node) {
				node.classList[action](cssClass)
			}
		}

		const addCssClass = (type) => {
			applyCssClass(type, 'add')
		}


		const removeCssClass = (type) => {
			applyCssClass(type, 'remove')
		}

		const attachHelper = () => {
			if (hasHelperSlot) {
				helperNode = helper.value
				sortableContainer.value.insertBefore(helperNode, draggedItem)
				draggedItem.insertAdjacentElement('afterend', helperNode)
			} else if (groupInfo.value.pull === 'clone') {
				const clone = draggedItem.cloneNode(true)
				sortableContainer.value.insertBefore(clone, draggedItem)
				helperNode = clone
			} else {
				helperNode = draggedItem
			}

			addCssClass('helper')
		}

		function detachHelper () {
			if (helperNode) {
				// Remove css class
				removeCssClass('helper')

				if (hasHelperSlot || groupInfo.value.pull === 'clone') {
					// Remove helper
					const helperContainer = helperNode.parentNode
					// There are cases where the placeholder is not yet present in the dom
					if (helperContainer) {
						helperContainer.removeChild(helperNode)
					}
				}
			}
		}

		const attachPlaceholder = () => {
			if (!props.placeholder) {
				return
			}

			if (hasPlaceholderSlot) {
				placeholderNode = placeholder.value
			} else {
				placeholderNode = draggedItem.cloneNode(true)
				placeholderNode.style.visibility = 'hidden'
			}

			if (placeholderNode && groupInfo.value.pull !== 'clone') {
				sortableContainer.value.insertBefore(placeholderNode, draggedItem)
			}

			addCssClass('placeholder')
		}

		function detachPlaceholder () {
			if (placeholderNode) {
				// Remove placeholder css class
				removeCssClass('placeholder')

				const placeholderContainer = placeholderNode.parentNode
				// There are cases where the placeholder is not yet present in the dom
				if (placeholderContainer) {
					placeholderContainer.removeChild(placeholderNode)
				}
			}
		}

		const finishDrag = () => {
			clearTimeout(dragTimeout)
			detachEvents()

			if (dragging.value) {
				dragging.value = false
				currentDocument.body.style.userSelect = null

				// Add css class for body
				removeCssClass('body')
				removeCssClass('source')
				removeCssClass('source:container')
				removeCssClass('placeholder:container')

				detachPlaceholder()
				detachHelper()

				// If the revert option is set to true the element will regain initial position
				if (helperNode) {
					if (props.revert) {
						helperNode.style.position = null
						helperNode.style.left = null
						helperNode.style.top = null
						helperNode.style.width = null
						helperNode.style.height = null
						helperNode.style.zIndex = null
						helperNode.style.transform = null
					}

					if (props.allowDuplicate && duplicateValue) {
						draggedItem.style.display = null
						draggedItem.style.opacity = null
					}


					helperNode.style.willChange = null
					helperNode.style.pointerEvents = null
					helperNode.style.zIndex = null
				}

				if (hasHelperSlot) {
					draggedItem.style.display = null
				}

				const { from, to, startIndex, newIndex, placeBefore } = lastEvent.data

				let draggedValueModel = null

				// Check to see if a change was made
				if (from && to && newIndex !== -1) {
					// Send event for second list
					const toVm = to.__SORTABLE_INFO__

					// Update values if exists
					if (props.modelValue !== null) {
						let modifiedNewIndex = placeBefore ? newIndex : newIndex + 1
						draggedValueModel = props.duplicateCallback ? props.duplicateCallback(props.modelValue[startIndex]) : props.modelValue[startIndex]

						if (from === to && startIndex !== newIndex) {
							updatePositionInList(startIndex, modifiedNewIndex)
						} else if (from !== to) {
							// Send 2 events for each container
							// Remove from first list
							if (!duplicateValue) {
								removeItemFromList(startIndex)
							}
							toVm.addItemToList(draggedValueModel, modifiedNewIndex)
						}
					}

					// Send drop Event
					const dropEvent = new DropEvent({
						...lastEvent.data,
						toVm,
						draggedValueModel,
						fromDraggedValueModel: props.modelValue
					})

					toVm.emit('drop', dropEvent)
				}

				// Trigger move event
				const endEvent = new EndEvent()

				// Send the event
				emit('end', endEvent)
				eventScheduler.cancel()

				// Cleanup
				currentDocument = null
				initialX = null
				initialY = null
				dimensions = null
				draggedItem = null
				dragDelayCompleted = null
				offset = 0
				dragItemInfo = null
			}
		}

		const updatePositionInList = (oldIndex, newIndex) => {
			if (props.modelValue) {
				const list = [...props.modelValue]
				if (oldIndex >= newIndex) {
					list.splice(newIndex, 0, list.splice(oldIndex, 1)[0])
				} else {
					list.splice(newIndex - 1, 0, list.splice(oldIndex, 1)[0])
				}

				emit('update:modelValue', list)
			}
		}

		const addItemToList = (item, index) => {
			if (props.modelValue) {
				const list = [...props.modelValue]
				list.splice(index, 0, item)
				emit('update:modelValue', list)
			}
		}

		const removeItemFromList = (index) => {
			if (props.modelValue) {
				const list = [...props.modelValue]
				list.splice(index, 1)

				emit('update:modelValue', list)
			}
		}

		const onDraggableMouseMove = (event) => {
			if (dragging.value) {
				eventScheduler.move(event)
			} else {
				const { clientX, clientY } = event
				const xDistance = Math.abs(clientX - initialX)
				const yDistance = Math.abs(clientY - initialY)
				if (dragDelayCompleted && (xDistance >= props.dragTreshold || yDistance >= props.dragTreshold)) {
					dragging.value = true
					// Wait a cycle so the placeholder and helper to render
					nextTick(() => {
						startDrag(event)
					})
				}
			}
		}

		/**
		 * Returns information like HTMLElement, index and other usefull info from an HTMLElement
		 *
		 * @param {HTMLElement} target The target for which we need to get the info.
		 * @returns {object} The information about the requested target
		 */
		const getInfoFromTarget = (target) => {
			const validItem = closest(target, props.draggable, sortableContainer.value)
			const sortableDomElements = getDomeElementsFromSortableItems()
			const item = sortableDomElements.includes(validItem) ? validItem : false
			const index = sortableDomElements.indexOf(item)

			return {
				container: sortableContainer.value,
				item,
				index,
				newIndex: index,
				group: groupInfo
			}
		}

		/**
		 * Checks if the target can be pulled from list
		 * @param {object} dragItemInfo The drag item info.
		 * @returns {boolean} If the dragged item can be placed inside current container
		 */
		const canPull = () => {
			if (groupInfo.value.pull === false) {
				return false
			}

			return true
		}

		/**
		 * Returns true if provided component tag string is of type transition-group
		 *
		 */
		const componentIsTransitionGroup = (componentTag) => {
			return ['transition-group', 'TransitionGroup'].includes(componentTag)
		}

		const onMouseMove = (event) => {
			let { clientX, clientY } = event
			let offset = {
				left: 0,
				top: 0
			}
			// const { document: currentDocument } = event.view

			// Check to see if we need to duplicate the elemeent
			if (props.allowDuplicate && event.ctrlKey) {
				draggedItem.style.display = null
				draggedItem.style.opacity = 0.2
				duplicateValue = true
			} else {
				// draggedItem.style.display = 'none'
				draggedItem.style.opacity = null
				duplicateValue = false
			}

			// Calculate offset in case of iframes
			if (document !== currentDocument) {
				// offset = memoizedGetOffset(currentDocument, document)
			}

			// Calculate moves
			const movedX = clientX + offset.left - initialX
			const movedY = clientY + offset.top - initialY

			// Apply style
			helperNode.style.transform = `translate3d(${movedX}px, ${movedY}px, 0)`

			// Set defaults
			let overItem = {
				container: null,
				item: null,
				index: -1
			}

			const target = currentDocument.elementFromPoint(clientX, clientY)

			if (target) {
				const to = closest(target, getSortableContainer)

				const sameContainer = to === sortableContainer.value

				if (sameContainer && !props.sort) {
					// Do nothing if we cannot sort inside same container
				} else if (to) {
					const targetVM = to.__SORTABLE_INFO__

					// check if we can drop
					const overItemInfo = targetVM.getInfoFromTarget(target)

					overItem = {
						...overItem,
						...overItemInfo
					}

					// Set draggable info for target
					dragItemInfo.to = overItem.container
					dragItemInfo.toItem = overItem.item

					if (overItem.container) {
						if (overItem.item) {
							const collisionInfoData = collisionInfo(event, overItem.item, targetVM)
							dragItemInfo.placeBefore = collisionInfoData.before
							const whereToPutPlaceholder = targetVM.getItemFromList(overItem.index)
							const nextSibling = whereToPutPlaceholder.nextElementSibling
							const insertBeforeElement = dragItemInfo.placeBefore ? whereToPutPlaceholder : nextSibling

							// Only move if it actually moved position
							movePlaceholderMemoized(overItem.container, insertBeforeElement, dragItemInfo.placeBefore)
							dragItemInfo.newIndex = overItem.index
						} else {
							if (targetVM.modelValue && targetVM.modelValue.length === 0) {
								// Empty sortable container
								dragItemInfo.newIndex = 0
								movePlaceholderMemoized(overItem.container, null, dragItemInfo.placeBefore)
							} else if (sameContainer && props.modelValue.length === 1) {
								movePlaceholderMemoized(overItem.container, null, dragItemInfo.placeBefore)
							}
						}
					}
				} else if (!props.preserveLastLocation) {
					dragItemInfo.to = null
					dragItemInfo.newIndex = null
					dragItemInfo.toItem = null
					dragItemInfo.placeBefore = null
					movePlaceholderMemoized(null, null, null)
				}
			}

			const {
				container: from,
				item,
				index:
				startIndex,
				to,
				newIndex,
				toItem,
				placeBefore
			} = dragItemInfo

			// Trigger move event
			const moveEvent = new MoveEvent({
				from,
				item,
				startIndex,
				to,
				newIndex,
				toItem,
				nativeEvent: event,
				placeBefore
			})

			// Send the event
			emit('move', moveEvent)

			if (moveEvent.isCanceled()) {
				// Finish dragging
				finishDrag()
			}

			lastEvent = moveEvent
		}

		const collisionInfo = (event, overItem, targetVm) => {
			const { clientX, clientY } = event
			const itemRect = overItem.getBoundingClientRect()
			const orientation = detectOrientation(targetVm)
			const center = orientation === 'horizontal' ? itemRect.width / 2 : itemRect.height / 2
			const before = orientation === 'horizontal' ? clientX < itemRect.left + center : clientY < itemRect.top + center

			return {
				before
			}
		}

		const detectOrientation = (targetVm) => {
			return targetVm.axis || 'vertical'
		}

		const getDomeElementsFromSortableItems = () => {
			return sortableItems.value.filter(el => el).map(el => {
				// Refs can be Vue components or directly dom nodes
				if (el instanceof HTMLElement) {
					return el
				} else {
					return el.$el
				}
			})
		}

		const getItemFromList = (index) => {
			const sortableDomElements = getDomeElementsFromSortableItems()
			return sortableDomElements[index]
		}

		const getSortableContainer = (target) => {
			return target && target.__SORTABLE_INFO__ && target.__SORTABLE_INFO__.canPut(dragItemInfo)
		}

		// Lifecycle
		onMounted(() => {
			eventScheduler = EventScheduler(getEvents())
			sortableContainer.value.__SORTABLE_INFO__ = sortableInfo
		})

		// Don't make this ref as it isn't necessary
		const movePlaceholderMemoized = memoizeOne(movePlaceholder)
		const sortableInfo = {
			group: props.group,
			axis: props.axis,
			getInfoFromTarget,
			canPut,
			getItemFromList,
			addItemToList,
			modelValue: props.modelValue,
			emit
		}

		const attachRef = function (children) {
			if (Array.isArray(children)) {
				children.forEach((child, i) => {
					if (child.type === Fragment) {
						const fragmentItems = attachRef(child.children)
					} else {
						const clone = cloneVNode(child, {
							ref: (el) => {
								sortableItems.value[i] = el
							}
						})

						children[i] = clone
					}
				})
			}

			return children
		}

		return () => {
			const childElements = []
			let i = 0
			childItems = attachRef(slots.default())

			if (slots.start) {
				childElements.push(slots.start())
			}

			childElements.push(childItems)

			// Add the helper node
			if (dragging.value) {
				if (slots.helper) {
					hasHelperSlot = true

					// Wrap the helper with our class
					childElements.push(
						h(
							'div',
							{
								class: 'zion-editor__sortable-helper',
								ref: helper
							},
							slots.helper()
						)
					)
				}

				if (slots.placeholder) {
					hasPlaceholderSlot = true

					// Wrap the helper with our class
					childElements.push(
						h(
							'div',
							{
								class: 'znpb-sortable__placeholder',
								ref: placeholder
							},
							slots.placeholder()
						)
					)
				}
			}

			if (slots.end) {
				childElements.push(slots.end())
			}

			return h(
				props.tag,
				{
					onMousedown: props.disabled ? null : onMouseDown,
					onDragstart: onDragStart,
					ref: sortableContainer,
					class: {
						[`vuebdnd__placeholder-empty-container`]: canShowEmptyPlaceholder.value
					}
				},
				[childElements]
			)
		}
	}
}
</script>

<style lang="scss">
.vuebdnd-draggable--active iframe {
	pointer-events: none;
}
</style>
