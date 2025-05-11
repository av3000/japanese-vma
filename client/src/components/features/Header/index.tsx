// @ts-nocheck
/* eslint-disable */
import React from 'react';
import { Button, Dropdown, Nav, NavDropdown, Navbar } from 'react-bootstrap';
import { useDispatch, useSelector } from 'react-redux';
import { Link, NavLink, useNavigate } from 'react-router-dom';
import { logout } from '@/store/actions/auth';
import './header.scss';

const Header: React.FC = () => {
	const currentUser = useSelector((state) => state.currentUser);
	const dispatch = useDispatch();
	const navigate = useNavigate();

	const handleLogout = () => {
		dispatch(logout());
		navigate('/', { replace: true });
		window.location.reload();
	};

	return (
		<Navbar expand="lg">
			<Navbar.Brand as={Link} to="/">
				JPLearning
			</Navbar.Brand>
			<Navbar.Toggle aria-controls="basic-navbar-nav" />
			<Navbar.Collapse id="basic-navbar-nav">
				<Nav className="mx-auto">
					<Nav.Link as={Link} to="/articles">
						Articles
					</Nav.Link>
					<Nav.Link as={Link} to="/lists">
						Lists
					</Nav.Link>
					<NavDropdown title="Japanese Material" id="material-nav-dropdown">
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
					<Nav.Link as={Link} to="/community">
						Community
					</Nav.Link>
					{currentUser.isAuthenticated && (
						<>
							<Nav.Link as={Link} to="/dashboard">
								Dashboard
							</Nav.Link>
							<NavDropdown title="New" id="new-nav-dropdown">
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
						</>
					)}
				</Nav>
				{currentUser.isAuthenticated ? (
					<Nav>
						<Nav.Link as={Link} to="/dashboard">
							Logged in as <strong>{currentUser.user.name}</strong>
						</Nav.Link>
						<Button variant="outline-danger" onClick={handleLogout} className="ml-2">
							Logout
						</Button>
					</Nav>
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
};

export default Header;
