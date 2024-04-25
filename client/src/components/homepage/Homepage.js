import React from "react";
import { Link } from "react-router-dom";
import "./Homepage.css";
import ExploreArticleTimeline from "../article/ExploreArticleTimeline";
import ExploreListTimeline from "../list/ExploreListTimeline";
import InstagramIcon from "../../assets/icons/ig-icon.svg";
import FacebookIcon from "../../assets/icons/fb-icon.svg";
import LearnmoreIcon from "../../assets/icons/expand-more-icon.svg";

const Homepage = ({ currentUser }) => {
  if (!currentUser.isAuthenticated) {
    return (
      <div className="fullpage">
        <div className="homepage">
          <div className="homepage-upper-page">
            <div className="homepage-left">
              <div className="homepage-left-column">
                <div className="home-hero-header">
                  <h1>
                    Learn <span className="text-brand">japanese</span> in the
                    natural context{" "}
                  </h1>
                  <p>
                    JPLearning is the unique community and language learning
                    environment <span className="text-brand">for you</span> to
                    find &amp; share readings and material of your interest
                  </p>
                </div>
                <div className="home-hero-learnmore">
                  <a href="#readings" className="home-hero-learnmore-link">
                    Explore <img src={LearnmoreIcon} alt="expand-more-icon" />
                  </a>
                </div>
              </div>
            </div>
            <div className="homepage-right">
              <div className="home-hero-social-links float-right">
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
        <div className="container mt-4">
          <ExploreArticleTimeline />
          <ExploreListTimeline />
        </div>
      </div>
    );
  }

  return (
    <div className="container mt-4">
      <ExploreArticleTimeline />
      <ExploreListTimeline />
    </div>
  );
};

export default Homepage;
