import React, { Component } from "react";
import { Link } from 'react-router-dom';

class AuthForm extends Component {
    constructor(props) {
        super(props);
        this.state = {
            name: "",
            email: "",
            password: "",
            password_confirmation: ""
        };
    }

    handleSubmit = e => {
        e.preventDefault();
        
        const authType = this.props.signUp ? "register" : "login";
        this.props.onAuth(authType, this.state).then(() => {
            console.log("its ok: ")

            this.props.history.push("/");
          })
          .catch(() => {
            return;
          });
    }

    handleChange = e => {
        this.setState({
            [e.target.name]: e.target.value
        });
    };

    render(){
        const { email, name, password, password_confirmation, checkTerms } = this.state;
        const { heading, buttonText, signUp, errors, history, removeError } = this.props; 

        history.listen(() => {
            removeError();
        });

        return (
            <div className="container">
                <div className="row justify-content-md-center text-center mt-5">
                    <div className="col-md-6">
                        <form onSubmit={this.handleSubmit}>
                            <h2 >{heading}</h2>
                            { signUp ? ( <h6 className="mb-5">Already have an account? <Link to="/login">Login.</Link> </h6> )
                            : (<h6 className="mb-5">Don't have an account yet? <Link to="/register">Create now.</Link> </h6>)
                            }
                            {errors.message && ( <div className="alert alert-danger"> {errors.message} ERORAS </div>
                            )}
                            <label className="mt-3" htmlFor="email">Email:</label>
                            <input 
                                className="form-control"
                                id="email" 
                                name="email" 
                                onChange={this.handleChange}
                                value={email} 
                                type="text"
                            />
                            <label className="mt-3" htmlFor="password">Password:</label>
                            <input 
                                className="form-control"
                                id="password" 
                                name="password" 
                                onChange={this.handleChange}
                                type="password"
                            />
                            { signUp && (
                                <div>
                                    <label className="mt-3" htmlFor="password_confirmation">Confirm password:</label>
                                    <input 
                                        className="form-control"
                                        id="password_confirmation" 
                                        name="password_confirmation" 
                                        onChange={this.handleChange}
                                        type="password"
                                    />
                                    <label className="mt-3" htmlFor="name">Username:</label>
                                    <input 
                                        className="form-control"
                                        id="name" 
                                        name="name" 
                                        onChange={this.handleChange}
                                        value={name} 
                                        type="text"
                                    />
                                </div>
                            )}
                            <button type="submit" className="btn btn-outline-primary col-md-3 brand-button mt-5">
                                {buttonText}
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        )
    }
};

export default AuthForm;