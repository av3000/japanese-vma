import React from "react";
import { Link } from "react-router-dom";
import InstagramIcon from "@/assets/icons/ig-icon.svg";
import FacebookIcon from "@/assets/icons/fb-icon.svg";
import "./footer.scss";

const currentYear = new Date().getFullYear();

const Footer: React.FC = () => (
  <footer className="footer">
    <hr />
    <div className="u-container">
      <div className="navbar-header">
        <Link to="/" className="navbar-brand">
          <h4>JPLearning</h4>
        </Link>
      </div>

      <div className="row">
        <div className="col-lg-6 col-md-6 col-sm-12">
          <p>
            This site uses the{" "}
            <a href="http://www.edrdg.org/wiki/index.php/JMdict-EDICT_Dictionary_Project">
              JMdict
            </a>
            ,{" "}
            <a href="http://www.edrdg.org/wiki/index.php/KANJIDIC_Project">
              Kanjidic2
            </a>
            ,{" "}
            <a href="http://www.edrdg.org/enamdict/enamdict_doc.html">
              JMnedict
            </a>
            , and <a href="http://www.edrdg.org/krad/kradinf.html">Radkfile</a>{" "}
            dictionary files. These files are the property of the Electronic
            Dictionary Research and Development{" "}
            <a href="http://www.edrdg.org/">Group</a>, and are used in
            conformance with the Group's{" "}
            <a href="http://www.edrdg.org/edrdg/licence.html">licence</a>.
          </p>
          <br />
        </div>
        <div className="col-lg-6 col-md-6 col-sm-12">
          <p>
            Example sentences come from the{" "}
            <a href="http://tatoeba.org/">Tatoeba</a> project and are licensed
            under{" "}
            <a href="http://creativecommons.org/licenses/by/2.0/fr/">
              Creative Common CC-BY
            </a>
            .
          </p>
          <p>
            JLPT data comes from Jonathan Waller's JLPT Resources{" "}
            <a href="http://www.tanos.co.uk/jlpt/">page</a>.
          </p>
          <p>
            Contact Us{" "}
            <a href="mailto:jplearning.online@gmail.com">
              jplearning.online@gmail.com
            </a>{" "}
            or on Socials
          </p>
        </div>
      </div>

      <div className="row  d-flex align-items-center">
        <div className="col-12 text-center">
          <div className="home-hero-social-links mb-2">
            <Link to="https://www.facebook.com/" className="mr-2">
              <img src={FacebookIcon} alt="facebook-social-icon" />
            </Link>
            <Link to="https://www.instagram.com/">
              <img src={InstagramIcon} alt="instagram-social-icon" />
            </Link>
          </div>
          <p className="text-muted">
            Terms and Conditions | Copyright JPLearning &copy; {currentYear} |
            Privacy policy
          </p>
        </div>
      </div>
    </div>
  </footer>
);

export default Footer;
