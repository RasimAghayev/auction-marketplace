import { useToast } from 'vue-toastification'

export function useAppToast() {
  const toast = useToast()

  const success = (message: string, options?: { timeout?: number }) => {
    toast.success(message, { timeout: options?.timeout ?? 5000 })
  }

  const error = (message: string, options?: { timeout?: number }) => {
    toast.error(message, { timeout: options?.timeout ?? 8000 })
  }

  const warning = (message: string, options?: { timeout?: number }) => {
    toast.warning(message, { timeout: options?.timeout ?? 6000 })
  }

  const info = (message: string, options?: { timeout?: number }) => {
    toast.info(message, { timeout: options?.timeout ?? 5000 })
  }

  const dismiss = (id: number) => {
    toast.dismiss(id)
  }

  return {
    success,
    error,
    warning,
    info,
    dismiss,
  }
}