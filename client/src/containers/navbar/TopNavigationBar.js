import React, { Component } from "react";
import { Link, NavLink, withRouter } from "react-router-dom";
import { connect } from "react-redux";
import { Navbar, Nav, NavDropdown, Dropdown } from "react-bootstrap";
import { logout } from "../../store/actions/auth";
import "./Navbar.css";

class TopNavigationBar extends Component {
  constructor(props) {
    super(props);
    this.handleSelect = this.handleSelect.bind(this);
  }

  logout = (e) => {
    e.preventDefault();
    this.props.logout();
    this.props.history.push("/");
  };

  handleSelect(link) {
    this.props.history.push("/" + link);
  }

  render() {
    return (
      <Navbar expand="lg">
        <Navbar.Brand as={Link} to="/">
          JPLearning
        </Navbar.Brand>
        <Navbar.Toggle aria-controls="basic-navbar-nav" />
        <Navbar.Collapse id="basic-navbar-nav">
          <Nav className="nav navbar-nav navbar-right mx-auto">
            <NavDropdown title="Readings" id="basic-nav-dropdown">
              <Dropdown.Item as={Link} to="/articles">
                Articles
              </Dropdown.Item>
              <Dropdown.Item as={Link} to="/lists">
                Lists
              </Dropdown.Item>
            </NavDropdown>
            <NavDropdown title="Material" id="basic-nav-dropdown">
              <Dropdown.Item as={Link} to="/radicals">
                Radicals
              </Dropdown.Item>
              <Dropdown.Item as={Link} to="/kanjis">
                Kanjis
              </Dropdown.Item>
              <Dropdown.Item as={Link} to="/words">
                Words
              </Dropdown.Item>
              <Dropdown.Item as={Link} to="/sentences">
                Sentences
              </Dropdown.Item>
            </NavDropdown>
            {this.props.currentUser.isAuthenticated ? (
              <Nav.Link as={Link} to="/dashboard">
                Dashboard
              </Nav.Link>
            ) : (
              ""
            )}
            <Nav.Link as={Link} to="/community">
              Community
            </Nav.Link>
            {this.props.currentUser.isAuthenticated ? (
              <NavDropdown title="New" id="basic-nav-dropdown">
                <Dropdown.Item as={Link} to="/newarticle">
                  Article
                </Dropdown.Item>
                <Dropdown.Item as={Link} to="/newlist">
                  List
                </Dropdown.Item>
                <Dropdown.Divider />
                <Dropdown.Item as={Link} to="/newpost">
                  Community Post
                </Dropdown.Item>
              </NavDropdown>
            ) : (
              ""
            )}
          </Nav>
          {this.props.currentUser.isAuthenticated ? (
            <Nav>
              <NavLink className="nav-link mr-3" to="/dashboard">
                Logged as <strong> {this.props.currentUser.user.name} </strong>
              </NavLink>
            </Nav>
          ) : (
            ""
          )}
          {this.props.currentUser.isAuthenticated ? (
            <i onClick={this.logout} className="fas fa-sign-out-alt">
              Logout
            </i>
          ) : (
            <Nav>
              <NavLink className="nav-link" to="/register">
                Sign Up
              </NavLink>
              <NavLink className="nav-link" to="/login">
                Log In
              </NavLink>
            </Nav>
          )}
        </Navbar.Collapse>
      </Navbar>
    );
  }
}

function mapStateToProps(state) {
  return {
    currentUser: state.currentUser,
  };
}

export default withRouter(
  connect(mapStateToProps, { logout })(TopNavigationBar)
);
