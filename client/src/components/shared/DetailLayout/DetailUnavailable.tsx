import * as React from 'react';
import { parseApiError } from '@/api/apiError';
import { Link } from '@/components/shared/Link';
import styles from './DetailLayout.module.css';

/**
 * What a detail page says when its record cannot be shown, by the kind of failure. The status
 * comes from the HTTP response; the server's own text never reaches the page.
 */
export const unavailableMessage = (error: unknown, noun: 'article' | 'catalogue' | 'post'): string => {
	switch (parseApiError(error).kind) {
		case 'forbidden':
		case 'unauthenticated':
			return `This ${noun} is private.`;
		case 'notFound':
			return `This ${noun} doesn't exist. It may have been deleted.`;
		case 'unreachable':
			return "We couldn't reach the server. Check your connection and try again.";
		default:
			return `This ${noun} couldn't be loaded. Please try again.`;
	}
};

export interface DetailUnavailableProps {
	message: string;
	backTo: string;
	backLabel: string;
}

/** A dashed panel with the reason and the way back, in place of a detail page. */
export const DetailUnavailable: React.FC<DetailUnavailableProps> = ({ message, backTo, backLabel }) => (
	<section className={styles.unavailable} aria-labelledby="detail-unavailable">
		<h1 id="detail-unavailable" className={styles.unavailableTitle}>
			{message}
		</h1>
		<Link to={backTo}>{backLabel}</Link>
	</section>
);

export default DetailUnavailable;
