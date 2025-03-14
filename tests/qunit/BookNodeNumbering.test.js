( function () {
	QUnit.module( 'bluespice.bookshelf.processor.chapternumber.test', QUnit.newMwEnvironment() );

	QUnit.test( 'generateBookChapterNumbering list test', ( assert ) => {
		const items = [
			{ level: 1 },
			{ level: 2 },
			{ level: 2 },
			{ level: 1 },
			{ level: 2 },
			{ level: 3 },
			{ level: 2 },
			{ level: 3 },
			{ level: 3 },
			{ level: 1 },
			{ level: 2 },
			{ level: 3 },
			{ level: 4 },
			{ level: 3 },
			{ level: 3 },
			{ level: 2 },
			{ level: 3 },
			{ level: 3 },
			{ level: 1 },
			{ level: 1 },
			{ level: 2 },
			{ level: 1 },
			{ level: 2 },
			{ level: 3 },
			{ level: 2 },
			{ level: 3 },
			{ level: 3 }
		];

		const expectedNumbering = [
			'1.',
			'1.1.',
			'1.2.',
			'2.',
			'2.1.',
			'2.1.1.',
			'2.2.',
			'2.2.1.',
			'2.2.2.',
			'3.',
			'3.1.',
			'3.1.1.',
			'3.1.1.1.',
			'3.1.2.',
			'3.1.3.',
			'3.2.',
			'3.2.1.',
			'3.2.2.',
			'4.',
			'5.',
			'5.1.',
			'6.',
			'6.1.',
			'6.1.1.',
			'6.2.',
			'6.2.1.',
			'6.2.2.'
		];

		const processor = new ext.bookshelf.ui.data.BookNumberProcessor();
		const actualNumbering = processor.calculateNumbersFromList( items );
		assert.deepEqual( actualNumbering, expectedNumbering, 'Generated numbering matches expected numbering' );
	} );

	function getNumberForEachElement( items, processor, actualNumbering, parentNumbering = [] ) {
		items.forEach( ( item ) => {
			const number = processor.calculateNumberForElement( items, item );
			const currentNumbering = parentNumbering.concat( [ number ] );
			actualNumbering.push( currentNumbering.join( '.' ) );
			if ( item.items && item.items.length > 0 ) {
				getNumberForEachElement( item.items, processor, actualNumbering, currentNumbering );
			}
		} );

		return actualNumbering;
	}

	QUnit.test( 'generateBookChapterNumbering element test', ( assert ) => {
		const items = [
			{
				label: 'Subtopic 1',
				level: 1,
				name: 'menunode_12345',
				target: 'Subtopic 1',
				type: 'bs-bookshelf-chapter-wikilink-with-alias',
				items: [
					{
						label: 'Subtopic 1.1',
						level: 2,
						name: 'menunode_12346',
						target: 'Subtopic 1.1',
						type: 'bs-bookshelf-chapter-wikilink-with-alias',
						items: [
							{
								label: 'Subtopic 1.1.1',
								level: 3,
								name: 'menunode_12347',
								target: 'Subtopic 1.1.1',
								type: 'bs-bookshelf-chapter-wikilink-with-alias',
								items: []
							},
							{
								label: 'Subtopic 1.1.2',
								level: 3,
								name: 'menunode_12348',
								target: 'Subtopic 1.1.2',
								type: 'bs-bookshelf-chapter-wikilink-with-alias',
								items: []
							}
						]
					},
					{
						label: 'Subtopic 1.2',
						level: 2,
						name: 'menunode_12349',
						target: 'Subtopic 1.2',
						type: 'bs-bookshelf-chapter-wikilink-with-alias',
						items: []
					}
				]
			},
			{
				label: 'Subtopic 2',
				level: 1,
				name: 'menunode_67890',
				target: 'Subtopic 2',
				type: 'bs-bookshelf-chapter-wikilink-with-alias',
				items: []
			},
			{
				label: 'Subtopic 3',
				level: 1,
				name: 'menunode_67890',
				target: 'Subtopic 3',
				type: 'bs-bookshelf-chapter-wikilink-with-alias',
				items: []
			},
			{
				label: 'Subtopic 4',
				level: 1,
				name: 'menunode_12345',
				target: 'Subtopic 4',
				type: 'bs-bookshelf-chapter-wikilink-with-alias',
				items: [
					{
						label: 'Subtopic 4.1',
						level: 2,
						name: 'menunode_12346',
						target: 'Subtopic 4.1',
						type: 'bs-bookshelf-chapter-wikilink-with-alias',
						items: [
							{
								label: 'Subtopic 4.1.1',
								level: 3,
								name: 'menunode_12347',
								target: 'Subtopic 4.1.1',
								type: 'bs-bookshelf-chapter-wikilink-with-alias',
								items: []
							},
							{
								label: 'Subtopic 4.1.2',
								level: 3,
								name: 'menunode_12348',
								target: 'Subtopic 4.1.2',
								type: 'bs-bookshelf-chapter-wikilink-with-alias',
								items: []
							}
						]
					},
					{
						label: 'Subtopic 4.2',
						level: 2,
						name: 'menunode_12349',
						target: 'Subtopic 4.2',
						type: 'bs-bookshelf-chapter-wikilink-with-alias',
						items: []
					}
				]
			}
		];

		const expectedNumbering = [
			'1',
			'1.1',
			'1.1.1',
			'1.1.2',
			'1.2',
			'2',
			'3',
			'4',
			'4.1',
			'4.1.1',
			'4.1.2',
			'4.2'
		];

		const processor = new ext.bookshelf.ui.data.BookNumberProcessor();
		const actualNumbering = getNumberForEachElement( items, processor, [], [] );

		assert.deepEqual( actualNumbering, expectedNumbering, 'Generated numbering matches expected numbering' );
	} );
}() );
