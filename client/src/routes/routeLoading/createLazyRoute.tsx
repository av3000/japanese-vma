import { lazy, Suspense, type ComponentType, type ReactNode } from 'react';
import { PageLoading, type PageLoadingFamily } from '@/components/shared/PageLoading';

type LazyRouteOptions = {
	readonly family?: PageLoadingFamily;
	readonly visual?: ReactNode;
};

type LazyRouteModule = Promise<{ default: ComponentType }>;

type RouteLoadingBoundaryProps = LazyRouteOptions & {
	readonly children: ReactNode;
};

export const RouteLoadingBoundary = ({
	children,
	family = 'generic',
	visual,
}: RouteLoadingBoundaryProps) => (
	<Suspense fallback={<PageLoading family={family} visual={visual} />}>{children}</Suspense>
);

export const createLazyRoute = (
	load: () => LazyRouteModule,
	{ family = 'generic', visual }: LazyRouteOptions = {},
) => {
	const LazyPage = lazy(load);

	const LazyRoute = () => (
		<RouteLoadingBoundary family={family} visual={visual}>
			<LazyPage />
		</RouteLoadingBoundary>
	);

	LazyRoute.displayName = `LazyRoute(${family})`;

	return LazyRoute;
};
