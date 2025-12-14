import React from 'react';
import FacebookIcon from '@/assets/icons/fb-icon.svg';
import InstagramIcon from '@/assets/icons/ig-icon.svg';
import { ExploreArticleList, ExploreCustomList } from '@/components/features/Homepage';
import { Icon } from '@/components/shared/Icon';
import { Link } from '@/components/shared/Link';
import { useAuth } from '@/hooks/useAuth';
import './Homepage.scss';

const Homepage: React.FC = () => {
	/* eslint-disable */
	const { isAuthenticated } = useAuth();

	if (!isAuthenticated) {
		return (
			<div className="fullpage">
				<div className="homepage">
					<div className="homepage-upper-page">
						<div className="homepage-left">
							<div className="homepage-left-column">
								<div className="home-hero-header">
									<h1>
										Learn <span className="text-brand">japanese</span> in the natural context{' '}
									</h1>
									<p>
										JPLearning is the unique community and language learning environment{' '}
										<span className="text-brand">for you</span> to find &amp; share readings and
										material of your interest
									</p>
								</div>
								<div className="home-hero-explore">
									<a href="#readings" className="home-hero-explore-link">
										Explore <Icon size="md" name="chevron" />
									</a>
								</div>
							</div>
						</div>
						<div className="homepage-right">
							<div className="home-hero-social-links float-right m-2">
								<Link to="https://www.facebook.com/" className="mr-2">
									<img src={FacebookIcon} alt="facebook-social-icon" />
								</Link>
								<Link to="https://www.instagram.com/">
									<img src={InstagramIcon} alt="instagram-social-icon" />
								</Link>
							</div>
						</div>
					</div>
				</div>
				<div className="container mt-4" id="readings">
					<ExploreArticleList />
					<ExploreCustomList />
				</div>
			</div>
		);
	}

	return (
		<div className="container mt-4">
			<h1 className="text-center">Welcome to your feed!</h1>
			<ExploreArticleList />
			<ExploreCustomList />
		</div>
	);
};

export default Homepage;
