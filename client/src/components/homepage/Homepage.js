import React from 'react';
import { Link } from 'react-router-dom';
import ArrowIcon from '../../assets/icons/arrow-navigation-icon.svg';
import './Homepage.css';
import ArticleTimeline from '../article/ArticleTimeline';
import InstagramIcon from '../../assets/icons/ig-icon.svg';
import FacebookIcon from '../../assets/icons/fb-icon.svg';
import LearnmoreIcon from '../../assets/icons/expand-more-icon.svg';

const Homepage = ({ currentUser }) => {
    if( !currentUser.isAuthenticated ){

    return (
<div className="fullpage">
    <div className="homepage">
        <div className="homepage-upper-page">
            <div className="homepage-left">
                <div className="homepage-left-column">
                    <div className="home-hero-header">
                        <h1>Learn <span className="text-brand">japanese</span> in the natural context </h1>
                        <p>JPLearning is the unique community and language learning environment <span className="text-brand">for you</span> to find &amp; share readings and material of your interest</p>
                    </div>
                    <div className="home-hero-learnmore">
                        <a href="#readings" className="home-hero-learnmore-link">
                            Explore <img src={LearnmoreIcon} alt="expand-more-icon"/>
                        </a>
                    </div>
                </div>
            </div>
            <div className="homepage-right">
                {/* <div className="landing-register-form col-md-10">
                    <form action="" className="col-md-12">
                        <h5 className="text-center">Create an free account</h5>
                        <label htmlFor="email">Email</label>
                        <input id="email" type="text" className="form-control mb-3"/>
                        <label htmlFor="password">Password</label>
                        <input id="password" type="password" className="form-control mb-3"/>
                        <label htmlFor="terms">Confirm that you agree with  our <Link to="/termsandconditions"><u>terms &amp; conditions</u> </Link>
                         </label>
                        <input type="checkbox" className="form-control  col-md-2 mb-3"/>
                        <button className="btn btn-outline-primary form-control brand-button">
                            Submit
                        </button>
                    </form>
                </div> */}
                <div className="home-hero-social-links float-right">
                    <Link to="https://www.facebook.com/" className="mr-2">
                        <img src={FacebookIcon} alt="facebook-social-icon"/>
                    </Link>
                    <Link to="https://www.instagram.com/">
                        <img src={InstagramIcon} alt="instagram-social-icon"/>
                    </Link>
                </div>
            </div>
        </div>
    </div>
    <div className="container" > 
        <Link to="/articles" className="homepage-section-title" id="readings">
            <span>Readings <img src={ArrowIcon} alt="arrow icon" /> </span>
        </Link>
        <ArticleTimeline/>

        <Link to="/lists" className="homepage-section-title" id="lists">
            <span>Lists <img src={ArrowIcon} alt="arrow icon" /> </span>
        </Link>
        <ArticleTimeline/>
    </div>
</div>
    )
}

    return (
    <div className="container" > 
        <Link to="/articles" className="homepage-section-title" id="readings">
            <span>Readings <img src={ArrowIcon} alt="arrow icon" /> </span>
        </Link>
        <ArticleTimeline/>

        <Link to="/lists" className="homepage-section-title" id="lists">
            <span>Lists <img src={ArrowIcon} alt="arrow icon" /> </span>
        </Link>
        <ArticleTimeline/>
    </div>
    );
};

export default Homepage;