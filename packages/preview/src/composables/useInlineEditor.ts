import { ref, Ref, computed, markRaw } from 'vue'

const isInlineEditorOpen = ref(false)
const activeEditor: Ref<null | object> = ref(null)

export function useInlineEditor() {

	const closeEditor = () => {
		if (activeEditor.value) {
			activeEditor.value.hideEditor()
		}
		activeEditor.value = null
		isInlineEditorOpen.value = false

	}

	const opendEditor = (editor) => {

		if (activeEditor.value === editor) {
			return
		}
		closeEditor()
		activeEditor.value = editor
		isInlineEditorOpen.value = true
	}


	return {
		isInlineEditorOpen,
		closeEditor,
		opendEditor,
		activeEditor
	}
}