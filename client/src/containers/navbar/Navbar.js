import React, { Component } from 'react';
import { Link } from 'react-router-dom';
import { connect } from 'react-redux';
import './Navbar.css';
import { logout } from '../../store/actions/auth';


class Navbar extends Component {

    logout = e => {
        e.preventDefault();
        this.props.logout();
    }

    render(){
        return(
            <nav className="navbar navbar-expand">
                <div className="container-fluid">
                    <div className="navbar-header">
                        <Link to="/" className="navbar-brand">
                            JPLearning
                        </Link>
                    </div>
                    <ul className="nav navbar-nav navbar-right">
                        <li className="nav-item dropdown">
                            <div className="link-button nav-link dropdown-toggle" id="dropdown01" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" href="#">
                                Readings
                                <div className="dropdown-menu" aria-labelledby="dropdown01">
                                    <Link className="dropdown-item bb-2" to="/articles">Articles</Link>
                                    <Link className="dropdown-item bb-2" to="/lyrics">Lyrics</Link>
                                    <Link className="dropdown-item bb-2" to="/artists">Artists</Link>
                                    <Link className="dropdown-item" to="/lists">Lists</Link>
                                </div>
                            </div>
                        </li>
                        <li className="nav-item dropdown">
                            <div className="link-button nav-link dropdown-toggle" id="dropdown02" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" href="#">
                                Material
                                <div className="dropdown-menu" aria-labelledby="dropdown02">
                                    <Link className="dropdown-item bb-2" to="/radicals">Radicals</Link>
                                    <Link className="dropdown-item bb-2" to="/kanjis">Kanji</Link>
                                    <Link className="dropdown-item bb-2" to="/vocabulary">Vocabulary</Link>
                                    <Link className="dropdown-item" to="/sentences">Sentences</Link>
                                </div>
                            </div>
                        </li>
                        <li className="nav-item">
                            <Link className="nav-link" to="/dashboard">Dashboard</Link>
                        </li >
                        <li className="nav-item">
                            <Link className="nav-link" to="/community">Community</Link>
                        </li>
                        <li className="nav-item">
                            <Link className="nav-link" to="/about">About</Link>
                        </li>
                    </ul>
                    { this.props.currentUser.isAuthenticated ? (
                        <ul className="nav nav-navbar-nav navbar-right">
                            <li>
                                <button className="btn btn-outline-danger" onClick={this.logout}>Logout</button>
                            </li>
                        </ul> 
                    ) : (
                    <ul className="nav navbar-nav navbar-right-auth">
                        <li className="nav-item">
                            <Link className="nav-link" to="/register">Sign Up</Link>
                        </li>
                        <li className="nav-item">
                            <Link className="nav-link" to="/login">Log In</Link>
                        </li>
                    </ul>
                    )}
                </div>
            </nav>
        )
    }
}

function mapStateToProps(state) {
    return {
        currentUser: state.currentUser
    };
}

export default connect(mapStateToProps, { logout })(Navbar);