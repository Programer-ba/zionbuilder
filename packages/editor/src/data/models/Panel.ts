import { usePanels } from '../index'

export class Panel {
	id = ''
	position = 'relative'
	isDetached = false
	isDragging = false
	isExpanded = false
	isActive = false
	background = '#302c36'
	width = {
		value: 360,
		unit: 'px'
	}
	height = {
		value: null,
		unit: 'auto'
	}
	panelPos = 1

	constructor(config) {
		Object.assign(this, config)
	}

	set (key, value) {
		this[key] = value
	}

	close () {
		this.isActive = false
	}

	open () {
		this.isActive = true
		const { openPanels } = usePanels()

		// If this panel is part of a group,
		// close other panels from the same group that are already opened
		if (this.group !== 'undefined') {
			openPanels.value.forEach(panel => {
				if (typeof panel.group !== 'undefined' && panel.group === this.group && panel !== this.panel) {
					panel.close()
				}
			})
		}
	}

	toggle () {
		this.isActive = !this.isActive
	}
}