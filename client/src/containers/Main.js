import React from 'react';
import { Switch, Route, withRouter } from 'react-router-dom';
import { connect } from 'react-redux';
import { authUser } from '../store/actions/auth';
import { removeError} from '../store/actions/errors';
import AuthForm from '../components/authform/AuthForm';
import PageNotFound from '../components/errors/PageNotFound';
// Landing
import Homepage from '../components/homepage/Homepage';
// Articles
import ArticleDetails from '../components/article/ArticleDetails';
import ArticleTimeline from '../components/article/ArticleTimeline';
import ArticleForm from '../components/article/ArticleForm';
import ArticleEdit from '../components/article/ArticleEdit';
// Lists
import ListTimeline from '../components/list/ListTimeline';
import ListDetails from '../components/list/ListDetails';
import ListForm from '../components/list/ListForm';
import ListEdit from '../components/list/ListEdit';
// Radicals
import RadicalDetails from '../components/radical/RadicalDetails';
import RadicalTimeline from '../components/radical/RadicalTimeline';
// Kanjis
import KanjiDetails from '../components/kanji/KanjiDetails';
import KanjiTimeline from '../components/kanji/KanjiTimeline';
// Words
import WordDetails from '../components/word/WordDetails';
import WordTimeline from '../components/word/WordTimeline';
// Sentences
import SentenceDetails from '../components/sentence/SentenceDetails';
import SentenceTimeline from '../components/sentence/SentenceTimeline';
// Community
import PostDetails from '../components/post/PostDetails';
import PostTimeline from '../components/post/PostTimeline';
import PostForm from '../components/post/PostForm';
import PostEdit from '../components/post/PostEdit';
// Dashboard
import DashboardTimeline from '../components/dashboard/DashboardTimeline';

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
                {/* Articles */}
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
                        <ArticleTimeline

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
                {/* Custom Lists */}
                <Route
                    exact path="/newlist"
                    render={ props => {
                        return (
                            <ListForm
                                removeError={removeError}
                                errors={errors}
                                {...props} 
                            />
                        )
                    }}
                />
                <Route exact path="/list/edit/:list_id" component={ListEdit} />
                <Route exact path="/lists" render={props =>{
                    return (
                        <ListTimeline

                        />
                    )
                }}/>
                <Route exact path="/list/:list_id" render={props => {
                    return (
                        <ListDetails 
                            currentUser={currentUser}
                            {...props}
                        />
                    )
                }} />
                {/* Radicals */}
                <Route exact path="/radicals" render={props =>{
                    return (
                        <RadicalTimeline

                        />
                    )
                }}/>
                <Route exact path="/radical/:radical_id" render={props => {
                    return (
                        <RadicalDetails 
                            currentUser={currentUser}
                            {...props}
                        />
                    )
                }} />
                {/* Kanjis */}
                <Route exact path="/kanjis" render={props =>{
                    return (
                        <KanjiTimeline

                        />
                    )
                }}/>
                <Route exact path="/kanji/:kanji_id" render={props => {
                    return (
                        <KanjiDetails 
                            currentUser={currentUser}
                            {...props}
                        />
                    )
                }} />
                {/* Words */}
                <Route exact path="/words" render={props =>{
                    return (
                        <WordTimeline

                        />
                    )
                }}/>
                <Route exact path="/word/:word_id" render={props => {
                    return (
                        <WordDetails 
                            currentUser={currentUser}
                            {...props}
                        />
                    )
                }} />
                {/* Sentences */}
                <Route exact path="/sentences" render={props =>{
                    return (
                        <SentenceTimeline

                        />
                    )
                }}/>
                <Route exact path="/sentence/:sentence_id" render={props => {
                    return (
                        <SentenceDetails 
                            currentUser={currentUser}
                            {...props}
                        />
                    )
                }} />
                {/* Posts Community */}
                <Route exact path="/community" render={props =>{
                    return (
                        <PostTimeline

                        />
                    )
                }}/>
                <Route exact path="/community/:post_id" render={props => {
                    return (
                        <PostDetails 
                            currentUser={currentUser}
                            {...props}
                        />
                    )
                }} />
                <Route exact path="/community/edit/:post_id" component={PostEdit} />
                <Route
                    exact path="/newpost"
                    render={ props => {
                        return (
                            <PostForm
                                removeError={removeError}
                                errors={errors}
                                {...props} 
                            />
                        )
                    }}
                />
                {/* Dashboard */}
                <Route exact path="/dashboard" render={props =>{
                    return (
                        <DashboardTimeline
                            currentUser={currentUser}
                            {...props}
                        />
                    )
                }}/>

                {/* Not found component */}
                <Route component={PageNotFound} />
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