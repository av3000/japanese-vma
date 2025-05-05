import { format, parseISO, isThisYear } from 'date-fns';
import { ja } from 'date-fns/locale';

// Map of locales to their format strings and date-fns locale objects
const localeConfigs = {
	default: {
		formatString: 'MMM d, yyyy',
		formatStringNoYear: 'MMM d',
		withTime: 'MMM d, yyyy HH:mm',
		withTimeNoYear: 'MMM d HH:mm',
		locale: undefined,
	},
	ja: {
		formatString: 'yyyy年MM月dd日',
		formatStringNoYear: 'MM月dd日',
		withTime: 'yyyy年MM月dd日 HH時mm分',
		withTimeNoYear: 'MM月dd日 HH時mm分',
		locale: ja,
	},
	// Add more locales as needed
};

export function formatDate(dateString: string, localeKey = 'default', includeTime = false): string {
	if (!dateString) return dateString;

	try {
		const config = localeConfigs[localeKey] || localeConfigs.default;
		const date = parseISO(dateString);

		// Determine which format string to use based on whether it's this year
		let formatString;
		if (includeTime) {
			formatString = isThisYear(date) ? config.withTimeNoYear : config.withTime;
		} else {
			formatString = isThisYear(date) ? config.formatStringNoYear : config.formatString;
		}

		return format(date, formatString, { locale: config.locale });
	} catch {
		// If anything goes wrong, just return the original string
		return dateString;
	}
}
