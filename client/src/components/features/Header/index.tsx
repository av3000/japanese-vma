import React from 'react';
import { Button, Dropdown, Nav, NavDropdown, Navbar } from 'react-bootstrap';
import { Link, NavLink } from 'react-router-dom';
import { useAuth } from '@/hooks/useAuth';
import './header.scss';

const Header: React.FC = () => {
	const { user, isAuthenticated, logout } = useAuth();

	const handleLogout = () => {
		logout();
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
					{isAuthenticated && (
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
				{isAuthenticated && user ? (
					<Nav>
						<Nav.Link as={Link} to="/dashboard">
							Logged in as <strong>{user.name}</strong>
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
