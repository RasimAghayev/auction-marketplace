import { ref, onMounted, onUnmounted, computed } from 'vue'
import { getTimeRemaining } from '@/utils/helpers'

export function useCountdown(endDate: string | Date) {
  const timeRemaining = ref(getTimeRemaining(endDate instanceof Date ? endDate.toISOString() : endDate))
  let interval: ReturnType<typeof setInterval> | null = null

  const isExpired = computed(() => timeRemaining.value.expired)

  const formattedTime = computed(() => {
    const { days, hours, minutes, seconds } = timeRemaining.value
    if (timeRemaining.value.expired) return 'Ended'

    if (days > 0) {
      return `${days}d ${hours}h ${minutes}m`
    }

    if (hours > 0) {
      return `${hours}h ${minutes}m ${seconds}s`
    }

    if (minutes > 0) {
      return `${minutes}m ${seconds}s`
    }

    return `${seconds}s`
  })

  const start = () => {
    if (interval) return

    interval = setInterval(() => {
      const end = endDate instanceof Date ? endDate.toISOString() : endDate
      timeRemaining.value = getTimeRemaining(end)

      if (timeRemaining.value.expired && interval) {
        clearInterval(interval)
        interval = null
      }
    }, 1000)
  }

  const stop = () => {
    if (interval) {
      clearInterval(interval)
      interval = null
    }
  }

  const reset = () => {
    stop()
    timeRemaining.value = getTimeRemaining(endDate instanceof Date ? endDate.toISOString() : endDate)
  }

  onMounted(() => {
    start()
  })

  onUnmounted(() => {
    stop()
  })

  return {
    timeRemaining,
    isExpired,
    formattedTime,
    start,
    stop,
    reset,
  }
}