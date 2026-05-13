/**
 * Приводит ответ API к виду, ожидаемому страницей деталей конструкции.
 * В старых ответах некоторые поля могли отсутствовать, поэтому делаем дефолты.
 */

/**
 * @param {Record<string, unknown>|null|undefined} item
 * @returns {Array<{code: string, price: unknown, description: unknown, image_url: unknown, night_image_url: unknown}>}
 */
export function normalizeSideDetails(item) {
    const sides = Array.isArray(item?.sides) ? item.sides : []

    if (item?.side_details?.length) {
        return item.side_details
    }

    // Если в ответе есть только массив `sides`, строим `side_details`
    return sides.map((code) => ({
        code: String(code).toUpperCase(),
        price: null,
        description: null,
        image_url: null,
        night_image_url: null,
    }))
}

/**
 * @param {Record<string, unknown>|null|undefined} raw
 * @returns {null|{[key: string]: any, side_details: Array<any>, sides: string[], bookings: Array<any>}}
 */
export function normalizeAdvertisementRecord(raw) {
    if (!raw || typeof raw !== 'object') {
        return null
    }

    const side_details = normalizeSideDetails(raw)

    return {
        ...raw,
        side_details,
        sides: side_details.map((s) => s.code),
        bookings: Array.isArray(raw.bookings)
            ? raw.bookings.map((b) => ({
                  ...b,
                  booking_kind: b.booking_kind ?? b.bookingKind ?? 'firm',
              }))
            : [],
    }
}
