<template>
	<div
		class="znpb-admin-hidden-select__wrapper"
		@click="showContent = true, addEventListeners()"
	>
		<span class="znpb-admin-hidden-select__title">
			<slot></slot>
		</span>
		<div
			v-show="showContent"
			class="znpb-admin-hidden-select__content"
		>
			<transition name="fadeGrow">
				<div class="znpb-admin-hidden-select__content-slot znpb-fancy-scrollbar">
					<slot name="content"></slot>
				</div>
			</transition>
		</div>

	</div>
</template>

<script>
export default {
	name: 'HiddenContainer',
	data () {
		return {
			showContent: false
		}
	},
	methods: {
		addEventListeners () {
			document.addEventListener('click', this.closeOnOutsideClick)
		},
		removeEventListeners () {
			document.removeEventListener('click', this.closeOnOutsideClick)
		},
		closeOnOutsideClick (event) {
			if (!this.$el.contains(event.target)) {
				this.showContent = false
				this.removeEventListeners()
			}
		}
	},
	distroyed () {
		this.removeEventListeners()
	}
}
</script>

<style lang="scss">
.znpb-admin {
	&-hidden-select {
		&__content {
			position: absolute;
			top: 50%;
			z-index: 99;
			transform: translateY(-50%);

			&-slot {
				max-height: 230px;
				padding: 16px;
				background: var(--zb-surface-color);
				box-shadow: 0 0 5px 2px rgba(0, 0, 0, 0.05);
				border-radius: 3px;
			}
		}

		&__wrapper {
			position: relative;
			display: flex;
			max-width: 100%;
		}

		&__title {
			overflow: hidden;
			font-weight: 500;
			text-overflow: ellipsis;
			white-space: nowrap;
			cursor: pointer;

			@media (max-width: 767px) {
				line-height: 1.6;
				white-space: normal;
			}
		}
	}
}

.fadeGrow-enter-to,
.fadeGrow-leave-from {
	transition: transform 0.1s;
}
.fadeGrow-enter-from,
.fadeGrow-leave-to {
	transform: scale(0.9);
}
</style>
