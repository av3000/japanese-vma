import * as Sentry from '@sentry/react';

const sentryDsn = import.meta.env.VITE_SENTRY_DSN;
const sentryEnvironment = import.meta.env.VITE_SENTRY_ENVIRONMENT ?? import.meta.env.MODE;
const sentryRelease = import.meta.env.VITE_SENTRY_RELEASE;

let isInitialized = false;

export const isSentryEnabled = Boolean(sentryDsn);

export const initSentry = () => {
	if (!isSentryEnabled || isInitialized) {
		return;
	}

	Sentry.init({
		dsn: sentryDsn,
		enabled: true,
		environment: sentryEnvironment,
		release: sentryRelease,
	});

	isInitialized = true;
};

export const captureApiError = (error: unknown, context: Record<string, unknown>) => {
	if (!isSentryEnabled) {
		return;
	}

	Sentry.withScope((scope) => {
		scope.setTag('source', 'axios');

		Object.entries(context).forEach(([key, value]) => {
			scope.setExtra(key, value);
		});

		Sentry.captureException(error);
	});
};

export const sentryReactErrorHandler = Sentry.reactErrorHandler;
export { Sentry };
