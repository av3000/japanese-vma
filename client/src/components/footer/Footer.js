import React from 'react';
import { Link } from 'react-router-dom';
import InstagramIcon from '../../assets/icons/ig-icon.svg';
import FacebookIcon from '../../assets/icons/fb-icon.svg';
import './Footer.css';

const Footer = () => (
    <footer className="footer">
        <hr/>
      <div className="container-fluid">

        <div className="navbar-header">
            <Link to="/" className="navbar-brand">
                <h4>JPLearning</h4>
            </Link>
        </div>

        <div className="row">
            <div className="col-sm-6">
                <p>
                    This site uses the <a href="#">JMdict</a>, <a href="#">Kanjidic2</a>, <a href="#">JMnedict</a>, and <a href="#">Radkfile</a> dictionary files.
                    These files are the property of the Electronic Dictionary Research and Development <a href="#">Group</a>, and are used in conformance with the Group's <a href="#">licence</a>.
                </p>
                <br/>
            </div>
            <div className="col-sm-6">
                <p>
                    Example sentences come from the <a href="#">Tatoeba</a> project and are licensed under <a href="#">Creative Common CC-BY</a>.
                </p>
                <p>
                    JLPT data comes from Jonathan Waller's JLPT Resources <a href="#">page</a>.
                </p>
                Contact Us
                <p>jplearning.online@gmail.com or directly find us on Socials</p>
            </div>
        </div>

        <div className="row  d-flex align-items-center">
            <div className="col-md-12 col-lg-12 col-sm-12 text-center">
                <div className="home-hero-social-links mb-2">
                    <Link to="https://www.facebook.com/" className="mr-2">
                        <img src={FacebookIcon} alt="facebook-social-icon"/>
                    </Link>
                    <Link to="https://www.instagram.com/">
                        <img src={InstagramIcon} alt="instagram-social-icon"/>
                    </Link>
                </div>
                <p>
                Terms and Conditions | Copyright &copy; JPLearning 2020 | Privacy policy 
                </p>
            </div>
        </div>

      </div>
    </footer>
);

export default Footer;