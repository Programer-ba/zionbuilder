<template>

	<div class="znpb-icon-pack-modal__icons">
		<div
			class="znpb-icon-pack-modal__grid"
			v-if="iconList.length > 0"
		>
			<div
				class="znpb-icon-pack-modal-icon"
				v-for="(icon,i) in iconList"
				:key="i"
			>
				<div
					class="znpb-modal-icon-wrapper"
					:class="{'znpb-modal-icon-wrapper--active' : activeIcon===icon.name && activeFamily===family}"
					@click="$emit('icon-selected', icon)"
					@dblclick="$emit('update:modelValue', icon)"
				>
					<span
						:data-znpbiconfam="family"
						:data-znpbicon="unicode(icon.unicode)"
					></span>

				</div>
				<h4 class="znpb-modal-icon-wrapper__title">{{icon.name}}</h4>
			</div>
		</div>
		<span v-else>{{$translate('no_icons_in_package')}} {{family}}</span>
	</div>

</template>

<script>

export default {
	name: 'IconsPackGrid',
	props: {
		iconList: {
			type: Array,
			required: false
		},
		family: {
			type: String,
			required: false
		},
		activeIcon: {
			type: String,
			required: false
		},
		activeFamily: {
			type: String,
			required: false
		}
	},
	data () {
		return {
		}
	},
	methods: {
		unicode (unicode) {
			return JSON.parse(('"\\' + unicode + '"'))
		}
	},
	computed: {
	}
}
</script>
<style lang="scss">
.znpb-icon-pack-modal {
	&__icons {
		padding: 0 10px 20px;
	}
	&__grid {
		display: grid;

		grid-column-gap: 20px;
		grid-row-gap: 20px;
		grid-template-columns: repeat(auto-fill, minmax(80px, 1fr));

		h4.znpb-modal-icon-wrapper__title {
			margin-bottom: 0;
			font-size: 12px;
			font-weight: 500;
			line-height: 1;
			text-align: center;
		}
	}
}
.znpb-modal-icon-wrapper {
	display: flex;
	justify-content: center;
	align-items: center;
	height: 92px;
	margin-bottom: 5px;
	box-shadow: 0 5px 10px 0 var(--zb-surface-shadow);
	border: 1px solid var(--zb-elements-border-color);
	border-radius: 3px;
	cursor: pointer;

	span {
		color: var(--zb-surface-text-color);
		font-size: 28px;
		transition: all 0.2s;
	}
	&:hover {
		box-shadow: 0 5px 10px 0 var(--zb-surface-shadow-hover);

		span {
			color: var(--zb-surface-text-hover-color);
		}
	}

	&--active {
		border: 2px solid var(--zb-secondary-color);
		span {
			color: var(--zb-surface-text-active-color);
		}
	}
}
</style>
