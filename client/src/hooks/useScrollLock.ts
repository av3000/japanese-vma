import { useEffect, useRef, useState } from 'react';

/**
 * Adds support for locking and unlocking scroll on body
 *
 * Example use:
 * 	const setScrollLock = useScrollLock();
 *
 *  if (isActive) {
 *    setScrollLock(true);
 *  } else {
 *    setScrollLock(false);
 *  }
 * */
export const useScrollLock = (): ((isLocked: boolean) => void) => {
	const [isLocked, setIsLocked] = useState(false);
	const scrollPos = useRef<number>(0);

	useEffect(() => {
		const lock = (): void => {
			const scrollbarWidth = window.innerWidth - document.documentElement.clientWidth;
			scrollPos.current = window.pageYOffset;

			document.body.style.width = '100%';
			document.body.style.position = 'fixed';
			document.body.style.left = '0';
			document.body.style.paddingRight = `${scrollbarWidth}px`;
			document.body.style.top = `-${scrollPos.current}px`;
		};

		const unlock = (): void => {
			document.body.style.width = '';
			document.body.style.position = '';
			document.body.style.left = '';
			document.body.style.paddingRight = '';
			document.body.style.top = '';

			window.scrollTo(0, scrollPos.current);

			scrollPos.current = 0;
		};

		if (isLocked) {
			lock();
		}

		return (): void => {
			if (isLocked) {
				unlock();
			}
		};
	}, [isLocked]);

	return setIsLocked;
};
