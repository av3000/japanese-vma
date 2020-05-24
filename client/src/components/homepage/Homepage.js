import React from 'react';
import { Link } from 'react-router-dom';
import './Homepage.css';

const Homepage = () => (
    <div className="homepage">
        <div className="homepage-upper-page">
            <div className="homepage-left">
                <div className="homepage-left-column">
                    <div className="home-hero-header">
                        <h1>Learn <span className="text-brand">japanese</span> in the natural context </h1>
                        <p>JPLearning is the unique community and language learning environment <span className="text-brand">for you</span> to find &amp; share readings and material of your interest</p>
                    </div>
                    <div className="home-hero-learnmore">
                        <p>Learn more V</p>
                    </div>
                </div>
            </div>
            <div className="homepage-right">
                <div className="landing-register-form col-md-10">
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
                </div>
                <div className="home-hero-social-links float-right">
                    <p>FB | IG</p>
                </div>
            </div>
        </div>
    </div>
);

export default Homepage;

/* <div className="homepage-lower-page" >
            <div className="home-hero"></div>
            <div className="home-hero-links text-center">
                <Link className="text-brand">
                    Learn more V
                </Link>
            </div>
            <div className="background"></div>
        </div> */

            