import { ChangeDetectionStrategy, Component, EventEmitter, Input, Output } from '@angular/core';
import { MatDialog } from '@angular/material/dialog';
import { DetailedRecipe } from 'src/app/core/models/recipe';
import { RecipePublicShareDialogComponent } from '../recipe-public-share-dialog/recipe-public-share-dialog.component';

@Component({
  selector: 'app-recipe-detail-header',
  templateUrl: './recipe-detail-header.component.html',
  styleUrls: ['./recipe-detail-header.component.scss'],
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class RecipeDetailHeaderComponent {
  @Input() recipe!: DetailedRecipe;

  @Output() deleteRecipe = new EventEmitter<void>();

  constructor(private dialog: MatDialog) {}

  openPublicShareDialog() {
    this.dialog.open(RecipePublicShareDialogComponent, { data: { recipe: this.recipe } });
  }
}

