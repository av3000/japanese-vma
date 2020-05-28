import React from 'react';
import { Switch, Route, withRouter } from 'react-router-dom';
import { connect } from 'react-redux';
import { authUser } from '../store/actions/auth';
import { removeError} from '../store/actions/errors';
import Homepage from '../components/homepage/Homepage';
import ArticleDetails from '../components/article/ArticleDetails';
import ArticleListTimeline from '../components/article/ArticleListTimeline';
import ArticleForm from '../components/article/ArticleForm';
import ArticleEdit from '../components/article/ArticleEdit';
import AuthForm from '../components/authform/AuthForm';


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
                <Route
                    exact path="/newarticle"
                    render={ props => {
                        return (
                            <ArticleForm
                                removeError={removeError}
                                errors={errors}
                                {...props} 
                            />
                        )
                    }}
                />
                <Route exact path="/article/edit/:article_id" component={ArticleEdit} />
                <Route exact path="/articles" render={props =>{
                    return (
                        <ArticleListTimeline

                        />
                    )
                }}/>
                <Route exact path="/article/:article_id" render={props => {
                    return (
                        <ArticleDetails 
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