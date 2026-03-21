import * as Sentry from '@sentry/react';

const sentryDsn = import.meta.env.VITE_SENTRY_DSN;
const sentryEnvironment = import.meta.env.VITE_SENTRY_ENVIRONMENT ?? import.meta.env.MODE;
const sentryRelease = import.meta.env.VITE_SENTRY_RELEASE;
const sentrySmokeTestQueryParam = 'sentry_test';

let isInitialized = false;

export const isSentryEnabled = Boolean(sentryDsn);

const clearSmokeTestQueryParam = () => {
	if (typeof window === 'undefined') {
		return;
	}

	const url = new URL(window.location.href);
	url.searchParams.delete(sentrySmokeTestQueryParam);
	window.history.replaceState({}, document.title, url.toString());
};

export const triggerSentrySmokeTest = () => {
	if (!isSentryEnabled) {
		return null;
	}

	return Sentry.captureException(new Error(`Frontend Sentry smoke test (${new Date().toISOString()})`));
};

const maybeRunSentrySmokeTest = () => {
	if (typeof window === 'undefined') {
		return;
	}

	const url = new URL(window.location.href);
	if (url.searchParams.get(sentrySmokeTestQueryParam) !== '1') {
		return;
	}

	const eventId = triggerSentrySmokeTest();
	clearSmokeTestQueryParam();

	if (eventId) {
		console.info('Triggered frontend Sentry smoke test.', { eventId });
	}
};

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
	maybeRunSentrySmokeTest();
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
