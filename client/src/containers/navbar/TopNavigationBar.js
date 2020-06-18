import React, { Component } from 'react';
import { Link, NavLink, withRouter } from 'react-router-dom';
import { connect } from 'react-redux';
import './Navbar.css';
import { logout } from '../../store/actions/auth';
import { Navbar, Nav, NavDropdown, Dropdown, Form, FormControl, Button } from 'react-bootstrap';

class TopNavigationBar extends Component {
    constructor(props) {
        super(props);

        this.handleSelect = this.handleSelect.bind(this);
    }

    logout = e => {
        e.preventDefault();
        console.log("logoutina")
        this.props.logout();
        this.props.history.push("/");
    }
    
    handleSelect(link){
        console.log(link);
        this.props.history.push("/"+link);
    }

    render(){
        return(
            <Navbar  expand="lg">
            <Navbar.Brand as={Link} to="/">JPLearning</Navbar.Brand>
            <Navbar.Toggle aria-controls="basic-navbar-nav" />
            <Navbar.Collapse id="basic-navbar-nav">
                <Nav className="nav navbar-nav navbar-right mx-auto">
                <NavDropdown title="Readings" id="basic-nav-dropdown">
                    <Dropdown.Item as={Link} to="/articles">Articles</Dropdown.Item>
                    <Dropdown.Item as={Link} to="/lists">Lists</Dropdown.Item>
                </NavDropdown>
                <NavDropdown title="Material" id="basic-nav-dropdown">
                    <Dropdown.Item as={Link} to="/radicals">Radicals</Dropdown.Item>
                    <Dropdown.Item as={Link} to="/kanjis">Kanjis</Dropdown.Item>
                    <Dropdown.Item as={Link} to="/words">Words</Dropdown.Item>
                    <Dropdown.Item as={Link} to="/sentences">Sentences</Dropdown.Item>
                </NavDropdown>
                <Nav.Link as={Link} to="/dashboard">Dashboard</Nav.Link>
                <Nav.Link as={Link} to="/community">Community</Nav.Link>
                <NavDropdown title="New" id="basic-nav-dropdown">
                    <Dropdown.Item as={Link} to="/newarticle">Article</Dropdown.Item>
                    <Dropdown.Item as={Link} to="/newlist">List</Dropdown.Item>
                    <Dropdown.Divider/>
                    <Dropdown.Item as={Link} to="/newpost">Community Post</Dropdown.Item>
                </NavDropdown>
                </Nav>
                { this.props.currentUser.isAuthenticated ? (
                    <Nav>
                        <NavLink className="nav-link mr-3" to="/dashboard">
                            Logged as <strong> {this.props.currentUser.user.name} </strong>
                        </NavLink>
                    </Nav>
                ) : ("") }
                { this.props.currentUser.isAuthenticated ? ( 
                        <i onClick={this.logout} className="fas fa-sign-out-alt">Logout</i>
                    ) : (
                <Nav>
                    <NavLink className="nav-link" to="/register">Sign Up</NavLink>
                    <NavLink className="nav-link" to="/login">Log In</NavLink>
                </Nav>
                )}
            </Navbar.Collapse>
            </Navbar>

            // <Navbar className="navbar navbar-expand">
            //     <div className="container-fluid">
            //         <div className="navbar-header">
            //             <NavLink to="/" className="navbar-brand">
            //                 JPLearning
            //             </NavLink>
            //         </div>
            //         <ul className="nav navbar-nav navbar-right">
            //             <li className="nav-item dropdown">
            //                 <Dropdown >
            //                     <Dropdown.Toggle className="link-button nav-link" id="my-toggler-btn">
            //                         Readings
            //                     </Dropdown.Toggle>
            //                     <Dropdown.Menu>
            //                     <Dropdown.Item href="/articles">Articles</Dropdown.Item>
            //                     <Dropdown.Item href="/lists">Lists</Dropdown.Item>
            //                     </Dropdown.Menu>
            //                 </Dropdown>
            //             </li>
            //             <li className="nav-item dropdown">
            //                 <Dropdown >
            //                     <Dropdown.Toggle className="link-button nav-link" id="my-toggler-btn">
            //                         Material
            //                     </Dropdown.Toggle>
            //                     <Dropdown.Menu>
            //                     <Dropdown.Item href="/radicals">Radicals</Dropdown.Item>
            //                     <Dropdown.Item href="/kanjis">Kanjis</Dropdown.Item>
            //                     <Dropdown.Item href="/words">Words</Dropdown.Item>
            //                     <Dropdown.Item href="/sentences">Sentences</Dropdown.Item>
            //                     </Dropdown.Menu>
            //                 </Dropdown>
            //             </li>
            //             <li className="nav-item">
            //                 <NavLink className="nav-link" to="/dashboard">Dashboard</NavLink>
            //             </li >
            //             <li className="nav-item">
            //                 <NavLink className="nav-link" to="/community">Community</NavLink>
            //             </li>
            //             <li className="nav-item">
            //                 <NavLink className="nav-link" to="/about">About</NavLink>
            //             </li>
            //             <li className="nav-item">
            //                 <NavLink className="nav-link" to="/newarticle">New Article</NavLink>
            //             </li>
            //             <li className="nav-item">
            //                 <NavLink className="nav-link" to="/newlist">New List</NavLink>
            //             </li>
            //         </ul>
            //         { this.props.currentUser.isAuthenticated ? (
            //             <ul className="nav nav-navbar-nav navbar-right">
            //                 <li>
            //                     <button className="btn btn-outline-danger" onClick={this.logout}>
            //                         <i className="fas fa-sign-out-alt"></i>Logout
            //                     </button>
            //                     {/* dropdown UserName, NewArticle, NewList, Logout */}
            //                 </li>
            //             </ul> 
            //         ) : (
            //         <ul className="nav navbar-nav navbar-right-auth">
            //             <li className="nav-item">
            //                 <NavLink className="nav-link" to="/register">Sign Up</NavLink>
            //             </li>
            //             <li className="nav-item">
            //                 <NavLink className="nav-link" to="/login">Log In</NavLink>
            //             </li>
            //         </ul>
            //         )}
            //     </div>
            // </Navbar>
        )
    }
}

function mapStateToProps(state) {
    return {
        currentUser: state.currentUser
    };
}

export default withRouter(connect(mapStateToProps, { logout })(TopNavigationBar));