import React, { Component } from "react";
import "../assets/css/style.css";

export default class App extends Component {
    render() {
        return (
            <div>
                <header id="header" className="header-transparent">
                    <div className="container">
                        <div id="logo" className="pull-left">
                            <h1>
                                <a href="#hero">JPLearning</a>
                            </h1>
                        </div>

                        <nav id="nav-menu-container">
                            <ul className="nav-menu">
                                <li>
                                    <a href="#about">Introduction</a>
                                </li>
                                <li>
                                    <a href="#documentation">API</a>
                                </li>
                                <li>
                                    <a href="#credits">Credits</a>
                                </li>
                            </ul>
                        </nav>
                    </div>
                </header>

                <section id="hero">
                    <div
                        className="hero-container"
                        data-aos="zoom-in"
                        data-aos-delay="100"
                    >
                        <h2>Japanese virtual e-learning environment</h2>
                        <a
                            href="https://jplearningonline.herokuapp.com"
                            className="btn-get-started"
                            target="_blank"
                        >
                            <i className="fas fa-globe"></i> Visit JPLearning
                        </a>
                        <a href="#about">
                            {" "}
                            Explore <i className="fas fa-angle-double-down"></i>{" "}
                        </a>
                    </div>
                </section>

                <main id="main">
                    <section id="about">
                        <div className="container" data-aos="fade-up">
                            <div className="row about-container">
                                <div className="col-lg-6 content order-lg-1 order-2">
                                    <h2 className="title">About JPLearning</h2>
                                    <p>
                                        Internet is full of various learning
                                        resources and understanding, tracking
                                        the progress, sorting out the learning
                                        material. To find the clear and
                                        structured way of learning is quite the
                                        challenge. By joining the JPLearning
                                        virtual e-learning environment, japanese
                                        learners can find all radicals, kanji
                                        and words in one place by saving them
                                        into customized personal lists or just
                                        use lists of other people! Want to find
                                        a way to practice your knowledge? Go to
                                        the readings section and find articles
                                        of your interest and knowledge
                                        competence to read. Want to use it in
                                        various devices? Download the pdf
                                        documents to user everywhere you want or
                                        even print out worksheets to practice by
                                        writing.
                                    </p>

                                    <div
                                        className="icon-box"
                                        data-aos="fade-up"
                                        data-aos-delay="100"
                                    >
                                        <div className="icon">
                                            <i className="fa fa-shopping-bag"></i>
                                        </div>
                                        <h4 className="title">
                                            <a href="">Radicals</a>
                                        </h4>
                                        <p className="description">
                                            Parts of kanji characters which help
                                            you write, read and understand the
                                            meaning easier.
                                        </p>
                                    </div>

                                    <div
                                        className="icon-box"
                                        data-aos="fade-up"
                                        data-aos-delay="200"
                                    >
                                        <div className="icon">
                                            <i className="fa fa-photo"></i>
                                        </div>
                                        <h4 className="title">
                                            <a href="">Kanji</a>
                                        </h4>
                                        <p className="description">
                                            Kanji characters are the main part
                                            of the japanese language. Without it
                                            - you are in a pretty bad situation!
                                            But it's ok, you can study them
                                            here.
                                        </p>
                                    </div>

                                    <div
                                        className="icon-box"
                                        data-aos="fade-up"
                                        data-aos-delay="300"
                                    >
                                        <div className="icon">
                                            <i className="fa fa-bar-chart"></i>
                                        </div>
                                        <h4 className="title">
                                            <a href="">Words</a>
                                        </h4>
                                        <p className="description">
                                            Result of adding up kanji characters
                                            and constructing meaningful
                                            sentences to have a discussions.
                                        </p>
                                    </div>

                                    <div
                                        className="icon-box"
                                        data-aos="fade-up"
                                        data-aos-delay="400"
                                    >
                                        <div className="icon">
                                            <i className="fa fa-bar-chart"></i>
                                        </div>
                                        <h4 className="title">
                                            <a href="">Sentences</a>
                                        </h4>
                                        <p className="description">
                                            Way of expressing your thoughts in
                                            japanese!
                                        </p>
                                    </div>

                                    <div
                                        className="icon-box"
                                        data-aos="fade-up"
                                        data-aos-delay="500"
                                    >
                                        <div className="icon">
                                            <i className="fa fa-bar-chart"></i>
                                        </div>
                                        <h4 className="title">
                                            <a href="">Articles</a>
                                        </h4>
                                        <p className="description">
                                            Readings which help find the context
                                            of using your knowledge.
                                        </p>
                                    </div>

                                    <div
                                        className="icon-box"
                                        data-aos="fade-up"
                                        data-aos-delay="600"
                                    >
                                        <div className="icon">
                                            <i className="fa fa-bar-chart"></i>
                                        </div>
                                        <h4 className="title">
                                            <a href="">Lists</a>
                                        </h4>
                                        <p className="description">
                                            Comfortable and accessable way of
                                            storing your wanted studying
                                            material or articles which you are
                                            reading at the moment or want to
                                            save for the future reference.
                                        </p>
                                    </div>
                                </div>

                                <div
                                    className="col-lg-6 background order-lg-2 order-1"
                                    data-aos="fade-left"
                                    data-aos-delay="100"
                                ></div>
                            </div>
                        </div>
                    </section>

                    <section id="documentation">
                        <div className="container" data-aos="fade-up">
                            <div className="section-header">
                                <h3 className="section-title">
                                    API Documentation
                                </h3>
                                <p className="section-description">
                                    JPLearning endpoints documentation.{" "}
                                </p>
                            </div>

                            <div className="row">
                                <p className="section-description">
                                    {" "}
                                    Root url:{" "}
                                    <i>
                                        https://www.jplearning.online/api
                                    </i>{" "}
                                </p>
                                <table className="table">
                                    <thead>
                                        <tr>
                                            <th scope="col">Endpoint</th>
                                            <th scope="col">Request type</th>
                                            <th scope="col">Parameters</th>
                                            <th scope="col">Results</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td>/articles</td>
                                            <td>GET</td>
                                            <td>-</td>
                                            <td>Paginated Articles</td>
                                        </tr>
                                        <tr>
                                            <td>/article</td>
                                            <td>POST</td>
                                            <td>
                                                <u>title_jp</u> - title
                                                <br />
                                                <u>content_jp</u> - body text
                                                <br />
                                                <u>source_link</u> - article
                                                original source
                                            </td>
                                            <td>
                                                New article with status of
                                                'pending'. After article is
                                                approved, the article will be
                                                accessable to the public
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>/article/[id]/like</td>
                                            <td>POST</td>
                                            <td>
                                                <u>id</u> - article id
                                                <br />
                                                <i>user_id</i> - id of the user
                                            </td>
                                            <td>
                                                Selected article gets +1 like.
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                                <p>
                                    <small>
                                        <strong>
                                            *documentation will be updated and
                                            relocated to separate pages with
                                            tables sections
                                        </strong>
                                    </small>
                                </p>
                                {/* <div className="col-lg-4 col-md-6" data-aos="zoom-in">
                            <div className="box">
                            <div className="icon"><a href=""><i className="fa fa-desktop"></i></a></div>
                            <h4 className="title"><a href="">Lorem Ipsum</a></h4>
                            <p className="description">Voluptatum deleniti atque corrupti quos dolores et quas molestias excepturi sint occaecati cupiditate non provident</p>
                            </div>
                        </div>
                        <div className="col-lg-4 col-md-6" data-aos="zoom-in">
                            <div className="box">
                            <div className="icon"><a href=""><i className="fa fa-bar-chart"></i></a></div>
                            <h4 className="title"><a href="">Dolor Sitema</a></h4>
                            <p className="description">Minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat tarad limino ata</p>
                            </div>
                        </div>
                        <div className="col-lg-4 col-md-6" data-aos="zoom-in">
                            <div className="box">
                            <div className="icon"><a href=""><i className="fa fa-paper-plane"></i></a></div>
                            <h4 className="title"><a href="">Sed ut perspiciatis</a></h4>
                            <p className="description">Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur</p>
                            </div>
                        </div>

                        <div className="col-lg-4 col-md-6" data-aos="zoom-in">
                            <div className="box">
                            <div className="icon"><a href=""><i className="fa fa-photo"></i></a></div>
                            <h4 className="title"><a href="">Magni Dolores</a></h4>
                            <p className="description">Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum</p>
                            </div>
                        </div>
                        <div className="col-lg-4 col-md-6" data-aos="zoom-in">
                            <div className="box">
                            <div className="icon"><a href=""><i className="fa fa-road"></i></a></div>
                            <h4 className="title"><a href="">Nemo Enim</a></h4>
                            <p className="description">At vero eos et accusamus et iusto odio dignissimos ducimus qui blanditiis praesentium voluptatum deleniti atque</p>
                            </div>
                        </div>
                        <div className="col-lg-4 col-md-6" data-aos="zoom-in">
                            <div className="box">
                            <div className="icon"><a href=""><i className="fa fa-shopping-bag"></i></a></div>
                            <h4 className="title"><a href="">Eiusmod Tempor</a></h4>
                            <p className="description">Et harum quidem rerum facilis est et expedita distinctio. Nam libero tempore, cum soluta nobis est eligendi</p>
                            </div>
                        </div> */}
                            </div>
                        </div>
                    </section>

                    <section id="credits">
                        <div className="container" data-aos="fade-up">
                            <div className="section-header">
                                <h3 className="section-title">Credits</h3>
                                {/* <p className="section-description">Sed ut perspiciatis unde omnis iste natus error sit voluptatem accusantium doloremque</p> */}
                            </div>
                            <div className="row counters">
                                <div className="col-lg-3 col-6 text-center">
                                    <span data-toggle="counter-up">232</span>
                                    <p>Articles</p>
                                </div>

                                <div className="col-lg-3 col-6 text-center">
                                    <span data-toggle="counter-up">521</span>
                                    <p>Lists</p>
                                </div>

                                <div className="col-lg-3 col-6 text-center">
                                    <span data-toggle="counter-up">1,463</span>
                                    <p>Posts</p>
                                </div>

                                <div className="col-lg-3 col-6 text-center">
                                    <span data-toggle="counter-up">1000</span>
                                    <p>Users</p>
                                </div>
                            </div>
                        </div>
                    </section>

                    <section id="call-to-action">
                        <div className="container">
                            <div className="row" data-aos="zoom-in">
                                <div className="col-lg-9 text-center text-lg-left">
                                    <h3 className="cta-title">
                                        Learn Japanese now!
                                    </h3>
                                    <p className="cta-text">
                                        We believe, that you will have the
                                        guidance you need to learn the japanese
                                        language with as less unnecessary waste
                                        of time as possible during this steep
                                        road of reaching the japanese language
                                        fluency. Like-minded people gathered as
                                        a strong community can achieve big
                                        things and we hope that You will become
                                        part of it.
                                    </p>
                                </div>
                                <div className="col-lg-3 cta-btn-container text-center">
                                    <a
                                        href="https://jplearningonline.herokuapp.com"
                                        className="cta-btn align-middle"
                                        target="_blank"
                                    >
                                        <i className="fas fa-globe"></i> Visit
                                        JPLearning
                                    </a>
                                </div>
                            </div>
                        </div>
                    </section>
                </main>

                <a href="#" className="back-to-top">
                    <i className="fa fa-chevron-up"></i>
                </a>
            </div>
        );
    }
}
