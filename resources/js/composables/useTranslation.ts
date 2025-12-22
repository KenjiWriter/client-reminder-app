import { usePage } from '@inertiajs/vue3';

export function useTranslation() {
    const page = usePage();

    /**
     * Translate a key with optional parameters
     * @param key Dot notation key (e.g., 'nav.calendar')
     * @param params Optional parameters for replacement (e.g., { name: 'John' })
     */
    const t = (key: string, params?: Record<string, string | number>): string => {
        const translations = page.props.translations as Record<string, any>;

        // Navigate through nested keys
        const keys = key.split('.');
        let value: any = translations;

        for (const k of keys) {
            if (value && typeof value === 'object' && k in value) {
                value = value[k];
            } else {
                // Fallback to key if translation not found
                return key;
            }
        }

        // If value is not a string, return the key
        if (typeof value !== 'string') {
            return key;
        }

        // Replace parameters if provided
        if (params) {
            return Object.entries(params).reduce((str, [paramKey, paramValue]) => {
                return str.replace(new RegExp(`{${paramKey}}`, 'g'), String(paramValue));
            }, value);
        }

        return value;
    };

    const locale = (page.props.locale as string) || 'pl';

    return { t, locale };
}
