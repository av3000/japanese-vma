import React from 'react';
import { Switch, Route, withRouter, Redirect } from 'react-router-dom';
import { connect } from 'react-redux';
import Homepage from '../components/homepage/Homepage';
import SingleArticle from '../components/article/SingleArticle';
import AuthForm from '../components/authform/AuthForm';
import { authUser } from '../store/actions/auth';
import { removeError} from '../store/actions/errors';

const Main = props => {
    const { authUser, errors, removeError, currentUser } = props;
    return(
        <div className="">
            <Switch>
                <Route exact path="/"
                       render={props => <Homepage currentUser={currentUser} {...props}/> } />
                <Route exact path="/register" render={props => {
                    return (
                        <AuthForm 
                            onAuth={authUser}
                            removeError={removeError}
                            signUp
                            errors={errors}
                            buttonText="Sign up"
                            heading="Join community today."
                            {...props}
                         />
                    )
                }}/>
                <Route exact path="/login" render={props => {
                    return (
                        <AuthForm 
                            onAuth={authUser}
                            removeError={removeError}
                            errors={errors}
                            buttonText="Log in"
                            heading="Welcome back."
                            {...props} 
                        />
                    )
                }}/>
                <Route exact path="/article/:article_id" render={props => {
                    return (
                        <SingleArticle 
                            currentUser={currentUser}
                            {...props}
                        />
                    )
                }} />
            </Switch>
        </div>
    );
};

function mapStateToProps(state) {
    return {
        currentUser: state.currentUser,
        errors: state.errors
    };
};

export default withRouter(connect(mapStateToProps, { authUser, removeError })(Main));