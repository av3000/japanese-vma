import React from 'react';
import { Container } from '@/components/shared/layout';
import styles from './NotFound.module.css';

const PageNotFound: React.FC = () => {
	return (
		<Container className={styles.page}>
			<h2>Yeah, page doesnt exist... it sucks!</h2>
		</Container>
	);
};

export default PageNotFound;
