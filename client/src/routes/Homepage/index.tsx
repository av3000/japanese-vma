import React from 'react';
import FacebookIcon from '@/assets/icons/fb-icon.svg';
import InstagramIcon from '@/assets/icons/ig-icon.svg';
import ExploreArticleList from '@/components/features/Homepage/ExploreArticleList';
import ExploreCatalogueList from '@/components/features/Homepage/ExploreCatalogueList';
import { Icon } from '@/components/shared/Icon';
import { Link } from '@/components/shared/Link';
import { Cluster, Container } from '@/components/shared/layout';
import { useAuth } from '@/hooks/useAuth';
import styles from './Homepage.module.css';

const Homepage: React.FC = () => {
	const { isAuthenticated } = useAuth();

	if (!isAuthenticated) {
		return (
			<>
				<section className={styles.hero}>
					<div className={styles.heroMain}>
						<div className={styles.heroColumn}>
							<div className={styles.heroHeader}>
								<h1 className={styles.heroTitle}>
									Learn <span className={styles.brand}>japanese</span> in the natural context{' '}
								</h1>
								<p className={styles.heroText}>
									JPLearning is the unique community and language learning environment{' '}
									<span className={styles.brand}>for you</span> to find &amp; share readings and
									material of your interest
								</p>
							</div>
							<div className={styles.heroExplore}>
								<a href="#readings" className={styles.heroExploreLink}>
									Explore <Icon size="md" name="chevron" />
								</a>
							</div>
						</div>
					</div>
					<div className={styles.heroAside}>
						<Cluster gap="xs" justify="end" className={styles.socialLinks}>
							<Link to="https://www.facebook.com/">
								<img src={FacebookIcon} alt="facebook-social-icon" />
							</Link>
							<Link to="https://www.instagram.com/">
								<img src={InstagramIcon} alt="instagram-social-icon" />
							</Link>
						</Cluster>
					</div>
				</section>
				<Container as="section" id="readings" className={styles.readings}>
					<ExploreArticleList />
					<ExploreCatalogueList />
				</Container>
			</>
		);
	}

	return (
		<Container as="section" className={styles.readings}>
			<h1 className={styles.feedTitle}>Welcome to your feed!</h1>
			<ExploreArticleList />
			<ExploreCatalogueList />
		</Container>
	);
};

export default Homepage;
