// @ts-nocheck
import { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { Button } from '@/components/shared/Button';
import { apiCall } from '@/services/api';

const SavedListForm = () => {
	const [formData, setFormData] = useState({
		title: '',
		type: '5',
		tags: '',
		publicity: '0',
	});
	const [errors, setErrors] = useState<string | null>(null);
	const [isLoading, setIsLoading] = useState(false);

	const navigate = useNavigate();

	const handleChange = (e) => {
		setFormData({
			...formData,
			[e.target.name]: e.target.value,
		});
	};

	const handleNewList = (e) => {
		e.preventDefault();
		setErrors(null);

		const { title } = formData;
		if (title.length < 3) {
			setErrors('Title requires to be at least 3char long!');
			return;
		}

		setIsLoading(true);

		const payload = {
			title: formData.title,
			type: parseInt(formData.type),
			tags: formData.tags,
			publicity: formData.publicity === '1',
		};

		postNewList(payload);
	};

	const postNewList = async (payload) => {
		try {
			const res = await apiCall('post', `/api/list`, payload);
			setIsLoading(false);
			navigate('/list/' + res.newList.id);
		} catch (err) {
			setIsLoading(false);

			let errorMessage = 'Something went wrong.';

			if (err?.title) {
				errorMessage = err.title[0];
			} else if (typeof err === 'string') {
				errorMessage = err;
			}

			setErrors(errorMessage);
		}
	};

	return (
		<div className="container">
			<div className="row justify-content-lg-center text-center">
				<form onSubmit={handleNewList} className="col-12">
					{errors && <div className="alert alert-danger">{errors}</div>}
					<label htmlFor="title" className="mt-3">
						<h4>Title</h4>
					</label>
					<input
						placeholder="List title"
						type="text"
						className="form-control"
						value={formData.title}
						name="title"
						onChange={handleChange}
					/>
					<label htmlFor="tags" className="mt-3">
						<h4>Add Tags</h4>
					</label>
					<input
						placeholder="#movie #booktitle #office"
						type="text"
						className="form-control"
						value={formData.tags}
						name="tags"
						onChange={handleChange}
					/>
					<label htmlFor="type" className="mt-3">
						List Type
					</label>
					<select name="type" value={formData.type} className="form-control" onChange={handleChange}>
						<option value="5">Radicals</option>
						<option value="6">Kanjis</option>
						<option value="7">Words</option>
						<option value="8">Sentences</option>
						<option value="9">Articles</option>
					</select>
					<label htmlFor="publicity" className="mt-3">
						Publicity
					</label>
					<select
						name="publicity"
						value={formData.publicity}
						className="form-control"
						onChange={handleChange}
					>
						<option value="1">Public</option>
						<option value="0">Private</option>
					</select>
					<Button type="submit" variant="outline" isLoading={isLoading}>
						Create the List
					</Button>
				</form>
			</div>
		</div>
	);
};

export default SavedListForm;
