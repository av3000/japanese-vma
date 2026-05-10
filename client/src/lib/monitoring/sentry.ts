const sentryDsn = import.meta.env.VITE_SENTRY_DSN;
const sentryEnvironment = import.meta.env.VITE_SENTRY_ENVIRONMENT ?? import.meta.env.MODE;
const sentryRelease = import.meta.env.VITE_SENTRY_RELEASE;
const sentrySmokeTestQueryParam = 'sentry_test';

let isInitialized = false;
let sentryModulePromise: Promise<typeof import('@sentry/react')> | null = null;

export const isSentryEnabled = Boolean(sentryDsn);

const loadSentry = async () => {
	sentryModulePromise ??= import('@sentry/react');
	return sentryModulePromise;
};

const getMaskedDsn = () => {
	if (!sentryDsn) {
		return null;
	}

	try {
		const url = new URL(sentryDsn);
		return `${url.protocol}//${url.host}${url.pathname}`;
	} catch {
		return 'invalid-dsn';
	}
};

const clearSmokeTestQueryParam = () => {
	if (typeof window === 'undefined') {
		return;
	}

	const url = new URL(window.location.href);
	url.searchParams.delete(sentrySmokeTestQueryParam);
	window.history.replaceState({}, document.title, url.toString());
};

export const triggerSentrySmokeTest = async () => {
	if (!isSentryEnabled) {
		return null;
	}

	const Sentry = await loadSentry();
	return Sentry.captureException(new Error(`Frontend Sentry smoke test (${new Date().toISOString()})`));
};

const maybeRunSentrySmokeTest = async () => {
	if (typeof window === 'undefined') {
		return;
	}

	const url = new URL(window.location.href);
	if (url.searchParams.get(sentrySmokeTestQueryParam) !== '1') {
		return;
	}

	console.info('Frontend Sentry smoke test requested.', {
		enabled: isSentryEnabled,
		environment: sentryEnvironment,
		release: sentryRelease ?? null,
		dsn: getMaskedDsn(),
	});

	const eventId = await triggerSentrySmokeTest();
	clearSmokeTestQueryParam();

	if (eventId) {
		console.info('Triggered frontend Sentry smoke test.', { eventId });
		return;
	}

	console.warn('Frontend Sentry smoke test did not send because Sentry is disabled in this build.');
};

export const initSentry = async () => {
	if (!isSentryEnabled || isInitialized) {
		return;
	}

	const Sentry = await loadSentry();

	Sentry.init({
		dsn: sentryDsn,
		enabled: true,
		environment: sentryEnvironment,
		release: sentryRelease,
	});

	isInitialized = true;
	await maybeRunSentrySmokeTest();
};

export const captureApiError = async (error: unknown, context: Record<string, unknown>) => {
	if (!isSentryEnabled) {
		return;
	}

	const Sentry = await loadSentry();

	Sentry.withScope((scope) => {
		scope.setTag('source', 'axios');

		Object.entries(context).forEach(([key, value]) => {
			scope.setExtra(key, value);
		});

		Sentry.captureException(error);
	});
};

export const captureRenderError = async (error: unknown, context: Record<string, unknown> = {}) => {
	if (!isSentryEnabled) {
		return;
	}

	const Sentry = await loadSentry();

	Sentry.withScope((scope) => {
		scope.setTag('source', 'react');

		Object.entries(context).forEach(([key, value]) => {
			scope.setExtra(key, value);
		});

		Sentry.captureException(error);
	});
};
