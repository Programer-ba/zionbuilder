import Model from '../Model'
// import { saveOptions } from './SaveOptions'
import { saveOptions, getSavedOptions } from '@zionbuilder/rest'
import { ref, Ref } from 'vue'

const isLoading: Ref<boolean> = ref(false)

export default class Options extends Model {

	defaults() {
		return {
			options: {
				allowed_post_types: ['post', 'page'],
				google_fonts: [],
				custom_fonts: [],
				typekit_token: '',
				typekit_fonts: [],
				local_colors: [],
				global_colors: [],
				local_gradients: [],
				global_gradients: [],
				user_roles_permissions: {},
				users_permissions: {}
			}
		}
	}

	getOptionValue(optionId: String, defaultValue = null) {
		if (typeof this.options[optionId] !== 'undefined') {
			return this.options[optionId]
		} else {
			return defaultValue
		}
	}

	updateOptionValue(optionId, { key, value }) {
		console.log('{ key, value }', { key, value })
		let optionIndex = this.options[optionId].indexOf(key)
		this.options[optionId].splice(optionIndex, 1, value)
		this.saveOptions()
	}

	addOptionValue(optionId, key: Object) {
		this.options[optionId].push(key)
		this.saveOptions()
	}

	deleteOptionValue(optionId, key: Object) {
		let fontConfig = this.options[optionId].find((font) => {
			return font.font_family === key
		})
		if (fontConfig) {
			let fontIndex = this.options[optionId].indexOf(fontConfig)
			if (fontIndex !== undefined) {
				this.options[optionId].splice(fontIndex, 1)
			} else {
				// eslint-disable-next-line
				console.warn('option for deletion was not found')
			}
		}
	}

	saveOptions() {

		return new Promise((resolve, reject) => {
			saveOptions(this.options)
				.then((response) => { })
				.catch(function (error) {
					reject(error)
				})
				.finally(() => {
					isLoading.value = false
					resolve()
				})
		})
	}

	fetchOptions() {
		return getSavedOptions().then((response) => {
			const newOptions = {
				...this.options,
				...response.data
			}
			this.options = newOptions
		})
	}
}