<template>
	<span
		v-if="iconConfig"
		:data-znpbiconfam="iconConfig.family"
		:data-znpbicon="iconUnicode"
	/>
</template>

<script>
import { computed } from 'vue'

export default {
	name: 'ElementIcon',
	props: {
		iconConfig: {
			type: Object,
			required: false,
			default () {
				return {
					family: 'Font Awesome 5 Brands Regular',
					name: 'wordpress-simple',
					unicode: 'uf411'
				}
			}
		}
	},
	setup (props) {
		const iconUnicode = computed(() => {
			return JSON.parse(`"\\${props.iconConfig.unicode}"`).trim()
		})

		return {
			iconUnicode
		}
	}
}
</script>