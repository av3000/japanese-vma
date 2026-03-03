import type { RefObject } from 'react';
import { useEffect } from 'react';
import { isSameOrContains } from '@/helpers/isSameOrContains';

/**
 * Listener for clicking outside of ref, which fires callback when that happens
 *
 * @param wrapperRef
 * @param callback
 * @param listenerCondition
 */
export const useOnClickAway = (
	wrapperRef: RefObject<Element | null>,
	callback: () => void,
	listenerCondition: boolean,
): void => {
	useEffect(() => {
		if (!wrapperRef.current) {
			return;
		}

		const handleClickAway = (event: MouseEvent): void => {
			const target = event.target as Element | null;
			if (!target) return;
			if (!isSameOrContains(wrapperRef.current as Element, target)) {
				callback();
			}
		};

		if (listenerCondition) {
			document.addEventListener('mouseup', handleClickAway);
		}

		return (): void => {
			document.removeEventListener('mouseup', handleClickAway);
		};
	}, [wrapperRef, listenerCondition, callback]);
};
