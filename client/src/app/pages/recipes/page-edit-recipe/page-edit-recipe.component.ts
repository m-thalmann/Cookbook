import { Component, OnDestroy, OnInit } from '@angular/core';
import { ActivatedRoute, Router } from '@angular/router';
import { Subscription } from 'rxjs';
import { ApiService } from 'src/app/core/api/api.service';
import { RecipeFull } from 'src/app/core/api/ApiInterfaces';
import { UserService } from 'src/app/core/auth/user.service';
import { SnackbarService } from 'src/app/core/services/snackbar.service';

@Component({
  selector: 'cb-page-edit-recipe',
  templateUrl: './page-edit-recipe.component.html',
  styleUrls: ['./page-edit-recipe.component.scss'],
})
export class PageEditRecipeComponent implements OnInit, OnDestroy {
  editRecipe: RecipeFull | null = null;

  loading = false;
  error = false;

  private subscriptions: Subscription[] = [];

  constructor(
    private route: ActivatedRoute,
    private api: ApiService,
    private snackbar: SnackbarService,
    private router: Router,
    private user: UserService
  ) {}

  ngOnInit() {
    this.subscriptions.push(
      this.route.params.subscribe((params) => {
        if (!this.loading && (!this.editRecipe || this.editRecipe.id !== params.id)) {
          this.load(params.id);
        }
      })
    );
  }

  async load(id: number) {
    this.loading = true;
    this.error = false;

    let res = await this.api.getRecipeById(id);

    if (res.isOK()) {
      if (res.value && (this.user.user?.isAdmin || res.value.user.id === this.user.user?.id)) {
        this.editRecipe = res.value;
      } else {
        this.snackbar.error('messages.recipes.user_not_owner');

        await this.router.navigateByUrl('/home');
      }
    } else if (res.isNotFound()) {
      this.snackbar.warn('messages.recipes.recipe_not_found');

      await this.router.navigateByUrl('/home');
    } else {
      this.error = true;

      console.error('Error loading recipe:', res.error);
    }

    this.loading = false;
  }

  ngOnDestroy() {
    this.subscriptions.forEach((subscription) => subscription.unsubscribe());
  }
}
